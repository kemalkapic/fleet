<?php

namespace App\Command;

use Minishlink\WebPush\VAPID;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:web-push:generate-keys', description: 'Generates a VAPID key pair for browser push notifications.')]
final class GenerateWebPushKeysCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $keys = VAPID::createVapidKeys();
        $output->writeln('WEB_PUSH_PUBLIC_KEY='.$keys['publicKey']);
        $output->writeln('WEB_PUSH_PRIVATE_KEY='.$keys['privateKey']);
        $output->writeln('WEB_PUSH_SUBJECT=mailto:noreply@kmmapps.com');

        return Command::SUCCESS;
    }
}
