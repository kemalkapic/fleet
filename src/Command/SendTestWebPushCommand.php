<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\Notification\TestNotificationSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:notifications:test', description: 'Sends five test push notifications and one summary email to a user.')]
final class SendTestWebPushCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TestNotificationSender $sender,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Recipient email address.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        $user = $this->users->findOneByEmail($email);
        if ($user === null) {
            $output->writeln('<error>Korisnik nije pronađen.</error>');

            return Command::FAILURE;
        }

        try {
            $result = $this->sender->send($user);
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Kreirano: %d, push isporučeno: %d, email: %s (%s).</info>',
            $result['notifications'],
            $result['pushSent'],
            $result['emailSent'] ? 'poslan' : 'nije poslan',
            $email,
        ));

        return Command::SUCCESS;
    }
}
