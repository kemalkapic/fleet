<?php

namespace App\Service\Notification;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class NotificationEmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private string $appName,
        private string $mailFrom,
    ) {
    }

    public function send(User $recipient, string $subject, string $message): void
    {
        $this->mailer->send((new Email())
            ->from(new Address($this->mailFrom, $this->appName))
            ->to(new Address($recipient->getEmail(), $recipient->getFullName() ?: $recipient->getEmail()))
            ->subject($subject)
            ->text($message));
    }
}
