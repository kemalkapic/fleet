<?php

namespace App\Service\Registration;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class ActivationEmailSender
{
    public function __construct(
        private MailerInterface $mailer,
        private string $appName,
        private string $mailFrom,
    ) {
    }

    public function send(User $user, string $activationUrl): void
    {
        $name = $user->getFullName() ?: $user->getEmail();
        $message = sprintf(
            "Pozdrav %s,\n\nregistracija u aplikaciji %s je uspješna.\n\nAktivirajte korisnički račun otvaranjem sljedećeg linka:\n%s\n\nLink vrijedi 24 sata. Ako niste zatražili registraciju, zanemarite ovu poruku.\n\n%s",
            $name,
            $this->appName,
            $activationUrl,
            $this->appName,
        );

        $this->mailer->send((new Email())
            ->from(new Address($this->mailFrom, $this->appName))
            ->to(new Address($user->getEmail(), $name))
            ->subject('Aktivirajte korisnički račun | '.$this->appName)
            ->text($message));
    }
}
