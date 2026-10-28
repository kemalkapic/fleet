<?php

namespace App\Service\Notification;

use App\Entity\Notification;
use App\Repository\CompanyRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WinterEquipmentNotifier
{
    private const SCHEDULE = [
        '03-27' => ['period' => 'end', 'daysBefore' => 5],
        '04-01' => ['period' => 'end', 'daysBefore' => 0],
        '10-27' => ['period' => 'start', 'daysBefore' => 5],
        '11-01' => ['period' => 'start', 'daysBefore' => 0],
    ];

    private const START_SUBJECT = 'Podsjetnik: Obavezna zimska oprema od 1. novembra';

    private const START_MESSAGE = <<<'TEXT'
Poštovani,

od 1. novembra počinje zakonska obaveza posjedovanja zimske opreme, bez obzira na vremenske uvjete.

Za putnička i teretna vozila potrebno je osigurati jednu od sljedećih opcija:

- zimske gume na svim točkovima;
- zimske gume na pogonskim točkovima uz lance za najmanje dva pogonska točka;
- ljetne gume s najmanje 4 mm profila uz lance za najmanje dva pogonska točka.

Provjerite opremu svih aktivnih vozila prije početka obaveznog perioda.
TEXT;

    private const END_SUBJECT = 'Podsjetnik: Obavezni period zimske opreme završava 1. aprila';

    private const END_MESSAGE = <<<'TEXT'
Poštovani,

zakonski period obaveznog posjedovanja zimske opreme završava 1. aprila.

Prije zamjene ili skladištenja opreme provjerite vremenske uvjete i stanje cesta. Nadležni organi mogu zahtijevati korištenje zimske opreme i izvan redovnog perioda kada na određenoj cesti vladaju zimski uvjeti.
TEXT;

    public function __construct(
        private CompanyRepository $companies,
        private UserRepository $users,
        private NotificationRepository $notifications,
        private NotificationEmailSender $emailSender,
        private NotificationInbox $inbox,
        private WebPushSender $webPushSender,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function send(\DateTimeImmutable $today): NotificationRunResult
    {
        $schedule = self::SCHEDULE[$today->format('m-d')] ?? null;
        if ($schedule === null) {
            return new NotificationRunResult();
        }

        $subject = $schedule['period'] === 'start' ? self::START_SUBJECT : self::END_SUBJECT;
        $message = $schedule['period'] === 'start' ? self::START_MESSAGE : self::END_MESSAGE;
        $eventKey = sprintf('winter_equipment_%s_%s', $schedule['period'], $today->format('Y-m-d'));
        $emailsSent = 0;
        $emailsFailed = 0;
        $notificationsSent = 0;
        $notificationsFailed = 0;
        $skipped = 0;

        foreach ($this->companies->findActive() as $company) {
            $recipients = $this->users->findActiveCompanyAdmins($company);
            if ($recipients === []) {
                ++$skipped;
                continue;
            }

            foreach ($recipients as $recipient) {
                $inboxItem = $this->inbox->create(
                    $recipient,
                    $company,
                    $eventKey,
                    $subject,
                    $message,
                    '/notifications',
                );
                $this->webPushSender->send($inboxItem, $schedule['daysBefore']);

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
                    ->setChannel(Notification::CHANNEL_EMAIL)
                    ->setTriggerDaysBefore($schedule['daysBefore'])
                    ->setRecipient($recipient->getEmail())
                    ->setMessageText($message)
                    ->setEventKey($eventKey)
                    ->setInboxItem($inboxItem);

                try {
                    $this->emailSender->send($recipient, $subject, $message);
                    $notification->markSent();
                    ++$emailsSent;
                    ++$notificationsSent;
                } catch (\Throwable $exception) {
                    $notification->markFailed($exception->getMessage());
                    ++$emailsFailed;
                    ++$notificationsFailed;
                }

                $this->entityManager->persist($notification);
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
}
