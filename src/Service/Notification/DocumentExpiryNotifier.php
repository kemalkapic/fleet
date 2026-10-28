<?php

namespace App\Service\Notification;

use App\Entity\FleetDocument;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\FleetDocumentRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DocumentExpiryNotifier
{
    private const THRESHOLDS = [15, 7, 1, -1];

    public function __construct(
        private FleetDocumentRepository $documents,
        private NotificationRepository $notifications,
        private UserRepository $users,
        private NotificationEmailSender $emailSender,
        private NotificationInbox $inbox,
        private WebPushSender $webPushSender,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function send(\DateTimeImmutable $today): NotificationRunResult
    {
        $batches = [];
        $skipped = 0;

        foreach (self::THRESHOLDS as $daysBefore) {
            $expiryDate = $today->modify(sprintf('%+d days', $daysBefore));

            foreach ($this->documents->findExpiringOn($expiryDate) as $document) {
                $company = $document->getCompany();
                if ($company === null) {
                    ++$skipped;
                    continue;
                }

                $recipients = $this->users->findActiveCompanyAdmins($company);
                if ($recipients === []) {
                    ++$skipped;
                    continue;
                }

                foreach ($recipients as $recipient) {
                    $message = $this->buildMessage($document, $daysBefore);
                    $title = $this->buildTitle($document, $daysBefore);
                    $eventKey = sprintf(
                        'document_%d_%s_%d',
                        $document->getId(),
                        $document->getValidUntil()->format('Ymd'),
                        $daysBefore,
                    );
                    $inboxItem = $this->inbox->create(
                        $recipient,
                        $company,
                        $eventKey,
                        $title,
                        $message,
                        '/documents/'.$document->getId().'/edit',
                        $document,
                    );
                    $this->webPushSender->send($inboxItem, $daysBefore);

                    if ($this->notifications->existsForSentEvent(
                        $company,
                        $eventKey,
                        Notification::CHANNEL_EMAIL,
                        $recipient->getEmail(),
                    )) {
                        ++$skipped;
                        continue;
                    }

                    $notification = (new Notification())
                        ->setCompany($company)
                        ->setDocument($document)
                        ->setChannel(Notification::CHANNEL_EMAIL)
                        ->setTriggerDaysBefore($daysBefore)
                        ->setRecipient($recipient->getEmail())
                        ->setMessageText($message)
                        ->setEventKey($eventKey)
                        ->setInboxItem($inboxItem);

                    $batchKey = ($company->getId() ?? spl_object_id($company)).'|'.mb_strtolower($recipient->getEmail());
                    $batches[$batchKey] ??= ['recipient' => $recipient, 'items' => []];
                    $batches[$batchKey]['items'][] = [
                        'document' => $document,
                        'daysBefore' => $daysBefore,
                        'message' => $message,
                        'notification' => $notification,
                    ];
                }
            }
        }

        return $this->sendBatches($batches, $skipped);
    }

    /**
     * @param array<string, array{
     *     recipient: User,
     *     items: list<array{
     *         document: FleetDocument,
     *         daysBefore: int,
     *         message: string,
     *         notification: Notification
     *     }>
     * }> $batches
     */
    private function sendBatches(array $batches, int $skipped): NotificationRunResult
    {
        $emailsSent = 0;
        $emailsFailed = 0;
        $notificationsSent = 0;
        $notificationsFailed = 0;

        foreach ($batches as $batch) {
            $items = $batch['items'];
            usort($items, static fn (array $left, array $right): int => $left['daysBefore'] <=> $right['daysBefore']);

            try {
                $this->emailSender->send(
                    $batch['recipient'],
                    $this->buildBatchSubject($items),
                    $this->buildBatchMessage($items),
                );

                ++$emailsSent;
                foreach ($items as $item) {
                    $item['notification']->markSent();
                    $this->entityManager->persist($item['notification']);
                    ++$notificationsSent;
                }
            } catch (\Throwable $exception) {
                ++$emailsFailed;
                foreach ($items as $item) {
                    $item['notification']->markFailed($exception->getMessage());
                    $this->entityManager->persist($item['notification']);
                    ++$notificationsFailed;
                }
            }
        }

        $this->entityManager->flush();

        return new NotificationRunResult(
            $emailsSent,
            $emailsFailed,
            $notificationsSent,
            $notificationsFailed,
            $skipped,
        );
    }

    private function buildMessage(FleetDocument $document, int $daysBefore): string
    {
        $ownerType = $document->getOwnerType() === FleetDocument::OWNER_DRIVER ? 'vozača' : 'vozilo';

        if ($daysBefore === -1) {
            return sprintf(
                'Rok za evidenciju "%s" za %s "%s" istekao je jučer, %s.',
                $document->getTypeLabel(),
                $ownerType,
                $document->getOwnerLabel(),
                $document->getValidUntil()->format('d.m.Y'),
            );
        }

        return sprintf(
            '"%s" za %s "%s" ističe %s, na datum %s.',
            $document->getTypeLabel(),
            $ownerType,
            $document->getOwnerLabel(),
            $daysBefore === 1 ? 'za 1 dan' : sprintf('za %d dana', $daysBefore),
            $document->getValidUntil()->format('d.m.Y'),
        );
    }

    private function buildTitle(FleetDocument $document, int $daysBefore): string
    {
        $prefix = match ($daysBefore) {
            -1 => 'Isteklo',
            1 => 'Hitno',
            7 => 'Upozorenje',
            default => 'Podsjetnik',
        };
        $expiry = match ($daysBefore) {
            -1 => 'isteklo je jučer',
            1 => 'ističe sutra',
            default => sprintf('ističe za %d dana', $daysBefore),
        };

        return sprintf('%s: %s za %s %s', $prefix, $document->getTypeLabel(), $document->getOwnerLabel(), $expiry);
    }

    /**
     * @param list<array{document: FleetDocument, daysBefore: int, message: string, notification: Notification}> $items
     */
    private function buildBatchSubject(array $items): string
    {
        if (count($items) > 1) {
            return sprintf('Pregled rokova: %d upozorenja', count($items));
        }

        $item = $items[0];
        $prefix = match ($item['daysBefore']) {
            -1 => 'Isteklo',
            1 => 'Hitno',
            7 => 'Upozorenje',
            default => 'Podsjetnik',
        };
        $expiry = match ($item['daysBefore']) {
            -1 => 'isteklo je jučer',
            1 => 'ističe za 1 dan',
            default => sprintf('ističe za %d dana', $item['daysBefore']),
        };

        return sprintf(
            '%s: %s za %s %s',
            $prefix,
            $item['document']->getTypeLabel(),
            $item['document']->getOwnerLabel(),
            $expiry,
        );
    }

    /**
     * @param list<array{document: FleetDocument, daysBefore: int, message: string, notification: Notification}> $items
     */
    private function buildBatchMessage(array $items): string
    {
        if (count($items) === 1) {
            return $items[0]['message'];
        }

        $lines = ['Pregled evidencija i rokova:', ''];
        foreach ($items as $index => $item) {
            $priority = match ($item['daysBefore']) {
                -1 => 'ISTEKLO',
                1 => 'HITNO',
                7 => 'UPOZORENJE',
                default => 'PODSJETNIK',
            };
            $lines[] = sprintf('%d. [%s] %s', $index + 1, $priority, $item['message']);
        }

        return implode(PHP_EOL, $lines);
    }

}
