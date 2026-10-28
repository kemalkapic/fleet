<?php

namespace App\Service\Notification;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\UserNotification;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TestNotificationSender
{
    public function __construct(
        private NotificationInbox $inbox,
        private WebPushSender $webPushSender,
        private NotificationEmailSender $emailSender,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /** @return array{notifications: int, pushSent: int, emailSent: bool} */
    public function send(User $user): array
    {
        $company = $user->getCompany();
        if (!$user->isActive() || $company === null) {
            throw new \InvalidArgumentException('Test se može poslati samo aktivnom korisniku koji pripada firmi.');
        }

        $messages = $this->buildMessages();
        $batchKey = (new \DateTimeImmutable())->format('YmdHisv');
        $items = [];
        $pushSent = 0;

        foreach ($messages as $index => $message) {
            $item = $this->inbox->create(
                $user,
                $company,
                sprintf('notification_test_%d_%s_%d', $user->getId(), $batchKey, $index),
                $message['title'],
                $message['body'],
                '/notifications',
            );
            $items[] = ['item' => $item, 'daysBefore' => $message['daysBefore']];
            if ($this->webPushSender->send($item, $message['daysBefore'])) {
                ++$pushSent;
            }
        }

        $emailSent = false;
        $emailFailure = null;
        try {
            $this->emailSender->send($user, 'Testni pregled obavijesti', $this->buildEmailMessage($messages));
            $emailSent = true;
        } catch (\Throwable $exception) {
            $emailFailure = $exception->getMessage();
        }

        foreach ($items as $entry) {
            /** @var UserNotification $item */
            $item = $entry['item'];
            $log = (new Notification())
                ->setCompany($company)
                ->setInboxItem($item)
                ->setChannel(Notification::CHANNEL_EMAIL)
                ->setTriggerDaysBefore($entry['daysBefore'])
                ->setRecipient($user->getEmail())
                ->setMessageText($item->getMessageText())
                ->setEventKey($item->getEventKey());
            $emailSent ? $log->markSent() : $log->markFailed($emailFailure ?? 'Testni email nije poslan.');
            $this->entityManager->persist($log);
        }
        $this->entityManager->flush();

        return ['notifications' => count($messages), 'pushSent' => $pushSent, 'emailSent' => $emailSent];
    }

    /** @return list<array{title: string, body: string, daysBefore: int}> */
    private function buildMessages(): array
    {
        return [
            [
                'title' => 'Podsjetnik: Registracija za TEST-001 ističe za 15 dana',
                'body' => sprintf('"Registracija" za vozilo "TEST-001" ističe za 15 dana, na datum %s.', (new \DateTimeImmutable('+15 days'))->format('d.m.Y')),
                'daysBefore' => 15,
            ],
            [
                'title' => 'Upozorenje: Registracija za TEST-001 ističe za 7 dana',
                'body' => sprintf('"Registracija" za vozilo "TEST-001" ističe za 7 dana, na datum %s.', (new \DateTimeImmutable('+7 days'))->format('d.m.Y')),
                'daysBefore' => 7,
            ],
            [
                'title' => 'Hitno: Registracija za TEST-001 ističe sutra',
                'body' => sprintf('"Registracija" za vozilo "TEST-001" ističe za 1 dan, na datum %s.', (new \DateTimeImmutable('+1 day'))->format('d.m.Y')),
                'daysBefore' => 1,
            ],
            [
                'title' => 'Isteklo: Registracija za TEST-001 istekla je jučer',
                'body' => sprintf('Rok za evidenciju "Registracija" za vozilo "TEST-001" istekao je jučer, %s.', (new \DateTimeImmutable('-1 day'))->format('d.m.Y')),
                'daysBefore' => -1,
            ],
            [
                'title' => 'Podsjetnik: Obavezna zimska oprema od 1. novembra',
                'body' => "Poštovani,\n\nod 1. novembra počinje zakonska obaveza posjedovanja zimske opreme, bez obzira na vremenske uvjete.\n\nZa putnička i teretna vozila potrebno je osigurati jednu od sljedećih opcija:\n\n- zimske gume na svim točkovima;\n- zimske gume na pogonskim točkovima uz lance za najmanje dva pogonska točka;\n- ljetne gume s najmanje 4 mm profila uz lance za najmanje dva pogonska točka.\n\nProvjerite opremu svih aktivnih vozila prije početka obaveznog perioda.",
                'daysBefore' => 5,
            ],
        ];
    }

    /** @param list<array{title: string, body: string, daysBefore: int}> $messages */
    private function buildEmailMessage(array $messages): string
    {
        $lines = ['Poštovani,', '', 'Ovo je testni pregled obavijesti iz aplikacije Vozi Miško.', ''];
        foreach ($messages as $index => $message) {
            $lines[] = sprintf('%d. %s', $index + 1, $message['title']);
            $lines[] = $message['body'];
            $lines[] = '';
        }

        return trim(implode(PHP_EOL, $lines));
    }
}
