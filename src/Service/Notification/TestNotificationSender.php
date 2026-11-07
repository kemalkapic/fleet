<?php

namespace App\Service\Notification;

use App\Entity\Notification;
use App\Entity\User;
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

        $message = $this->buildMessage();
        $batchKey = (new \DateTimeImmutable())->format('YmdHisv');
        $item = $this->inbox->create(
            $user,
            $company,
            sprintf('notification_test_%d_%s', $user->getId(), $batchKey),
            $message['title'],
            $message['body'],
            '/notifications',
        );

        $pushSent = $this->webPushSender->send($item) ? 1 : 0;
        $emailSent = false;
        $emailFailure = null;

        try {
            $this->emailSender->send($user, 'Testna obavijest aplikacije Vozi Miško', $this->buildEmailMessage($message));
            $emailSent = true;
        } catch (\Throwable $exception) {
            $emailFailure = $exception->getMessage();
        }

        $log = (new Notification())
            ->setCompany($company)
            ->setInboxItem($item)
            ->setChannel(Notification::CHANNEL_EMAIL)
            ->setRecipient($user->getEmail())
            ->setMessageText($item->getMessageText())
            ->setEventKey($item->getEventKey());

        $emailSent
            ? $log->markSent()
            : $log->markFailed($emailFailure ?? 'Testni email nije poslan.');

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return ['notifications' => 1, 'pushSent' => $pushSent, 'emailSent' => $emailSent];
    }

    /** @return array{title: string, body: string} */
    private function buildMessage(): array
    {
        return [
            'title' => 'Testna obavijest aplikacije Vozi Miško',
            'body' => 'Ovo je testna obavijest aplikacije Vozi Miško. Ako ste je primili, obavijesti za vaš korisnički račun rade ispravno.',
        ];
    }

    /** @param array{title: string, body: string} $message */
    private function buildEmailMessage(array $message): string
    {
        return implode(PHP_EOL, [
            'Poštovani,',
            '',
            $message['body'],
            '',
            'Ovaj email potvrđuje da slanje email obavijesti iz aplikacije radi ispravno.',
        ]);
    }
}
