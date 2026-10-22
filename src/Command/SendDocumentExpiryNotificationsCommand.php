<?php

namespace App\Command;

use App\Entity\FleetDocument;
use App\Entity\Notification;
use App\Repository\FleetDocumentRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:send-document-expiry-notifications', description: 'Sends document expiry notifications.')]
class SendDocumentExpiryNotificationsCommand extends Command
{
    private const THRESHOLDS = [15, 7, 1];

    public function __construct(
        private readonly FleetDocumentRepository $documents,
        private readonly NotificationRepository $notifications,
        private readonly UserRepository $users,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $appName,
        private readonly string $mailFrom,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sent = 0;
        $emailsSent = 0;
        $skipped = 0;
        $failed = 0;
        $emailsFailed = 0;
        $today = new \DateTimeImmutable('today');
        $batches = [];

        foreach (self::THRESHOLDS as $daysBefore) {
            $expiryDate = $today->modify(sprintf('+%d days', $daysBefore));

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
                    if ($this->notifications->existsForDocumentThreshold(
                        $document,
                        Notification::CHANNEL_EMAIL,
                        $daysBefore,
                        $recipient->getEmail(),
                    )) {
                        ++$skipped;
                        continue;
                    }

                    $messageText = $this->buildMessage($document, $daysBefore);
                    $notification = (new Notification())
                        ->setCompany($company)
                        ->setDocument($document)
                        ->setChannel(Notification::CHANNEL_EMAIL)
                        ->setTriggerDaysBefore($daysBefore)
                        ->setRecipient($recipient->getEmail())
                        ->setMessageText($messageText);

                    $batchKey = ($company->getId() ?? spl_object_id($company)).'|'.mb_strtolower($recipient->getEmail());
                    if (!isset($batches[$batchKey])) {
                        $batches[$batchKey] = [
                            'recipient' => $recipient,
                            'items' => [],
                        ];
                    }

                    $batches[$batchKey]['items'][] = [
                        'document' => $document,
                        'daysBefore' => $daysBefore,
                        'messageText' => $messageText,
                        'notification' => $notification,
                    ];
                }
            }
        }

        foreach ($batches as $batch) {
            $items = $batch['items'];
            usort($items, static fn (array $left, array $right): int => $left['daysBefore'] <=> $right['daysBefore']);
            $recipient = $batch['recipient'];

            try {
                $this->mailer->send((new Email())
                    ->from(new Address($this->mailFrom, $this->appName))
                    ->to(new Address($recipient->getEmail(), $recipient->getFullName() ?: $recipient->getEmail()))
                    ->subject($this->buildBatchSubject($items))
                    ->text($this->buildBatchMessage($items)));

                ++$emailsSent;
                foreach ($items as $item) {
                    $item['notification']->markSent();
                    $this->entityManager->persist($item['notification']);
                    ++$sent;
                }
            } catch (\Throwable $exception) {
                ++$emailsFailed;
                foreach ($items as $item) {
                    $item['notification']->markFailed($exception->getMessage());
                    $this->entityManager->persist($item['notification']);
                    ++$failed;
                }
            }
        }

        $this->entityManager->flush();

        $output->writeln(sprintf(
            'Emails sent: %d, emails failed: %d, notifications sent: %d, notifications failed: %d, skipped: %d',
            $emailsSent,
            $emailsFailed,
            $sent,
            $failed,
            $skipped,
        ));

        return Command::SUCCESS;
    }

    private function buildMessage(FleetDocument $document, int $daysBefore): string
    {
        $ownerType = $document->getOwnerType() === FleetDocument::OWNER_DRIVER ? 'vozača' : 'vozilo';

        return sprintf(
            '"%s" za %s "%s" ističe %s, na datum %s.',
            $this->formatDocumentType($document->getType()),
            $ownerType,
            $document->getOwnerLabel(),
            $daysBefore === 1 ? 'za 1 dan' : sprintf('za %d dana', $daysBefore),
            $document->getValidUntil()->format('d.m.Y'),
        );
    }

    private function buildSubject(FleetDocument $document, int $daysBefore): string
    {
        $prefix = match ($daysBefore) {
            1 => 'Hitno',
            7 => 'Upozorenje',
            default => 'Podsjetnik',
        };
        $expiry = $daysBefore === 1 ? 'ističe za 1 dan' : sprintf('ističe za %d dana', $daysBefore);

        return sprintf(
            '%s: %s za %s %s',
            $prefix,
            $this->formatDocumentType($document->getType()),
            $document->getOwnerLabel(),
            $expiry,
        );
    }

    /**
     * @param list<array{document: FleetDocument, daysBefore: int, messageText: string, notification: Notification}> $items
     */
    private function buildBatchSubject(array $items): string
    {
        if (count($items) === 1) {
            return $this->buildSubject($items[0]['document'], $items[0]['daysBefore']);
        }

        return sprintf('Pregled rokova: %d aktivna upozorenja', count($items));
    }

    /**
     * @param list<array{document: FleetDocument, daysBefore: int, messageText: string, notification: Notification}> $items
     */
    private function buildBatchMessage(array $items): string
    {
        if (count($items) === 1) {
            return $items[0]['messageText'];
        }

        $lines = ['Evidencije kojima se približava istek:', ''];

        foreach ($items as $index => $item) {
            $priority = match ($item['daysBefore']) {
                1 => 'HITNO',
                7 => 'UPOZORENJE',
                default => 'PODSJETNIK',
            };
            $lines[] = sprintf('%d. [%s] %s', $index + 1, $priority, $item['messageText']);
        }

        return implode(PHP_EOL, $lines);
    }

    private function formatDocumentType(string $type): string
    {
        return match ($type) {
            'registration' => 'Registracija',
            'periodic_inspection_3_months' => 'Periodički tehnički 3 mjeseca',
            'periodic_inspection_4_months' => 'Periodični tehnički 4 mjeseca',
            'periodic_inspection_6_months' => 'Periodični tehnički 6 mjeseci',
            'insurance' => 'Osiguranje',
            'attest' => 'Atest',
            'driving_licence' => 'Vozačka dozvola',
            'driver_card' => 'Kartica vozača - taho kartica',
            'medical_certificate' => 'Ljekarsko uvjerenje',
            'licence' => 'Licenca',
            'fire_extinguisher' => 'Protupožarni aparat',
            'first_aid' => 'Prva pomoć',
            'service' => 'Servis',
            'contract' => 'Ugovor',
            default => 'Ostalo',
        };
    }
}
