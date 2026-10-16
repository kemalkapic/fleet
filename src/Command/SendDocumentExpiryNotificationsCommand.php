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
        $skipped = 0;
        $failed = 0;
        $today = new \DateTimeImmutable('today');

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

                    try {
                        $this->mailer->send((new Email())
                            ->from(new Address($this->mailFrom, $this->appName))
                            ->to(new Address($recipient->getEmail(), $recipient->getFullName() ?: $recipient->getEmail()))
                            ->subject($this->buildSubject($document, $daysBefore))
                            ->text($messageText));

                        $notification->markSent();
                        ++$sent;
                    } catch (\Throwable $exception) {
                        $notification->markFailed($exception->getMessage());
                        ++$failed;
                    }

                    $this->entityManager->persist($notification);
                }
            }
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('Sent: %d, failed: %d, skipped: %d', $sent, $failed, $skipped));

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
            $daysBefore === 1 ? 'sutra' : sprintf('za %d dana', $daysBefore),
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
        $expiry = $daysBefore === 1 ? 'ističe sutra' : sprintf('ističe za %d dana', $daysBefore);

        return sprintf(
            '%s: %s za %s %s',
            $prefix,
            $this->formatDocumentType($document->getType()),
            $document->getOwnerLabel(),
            $expiry,
        );
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
