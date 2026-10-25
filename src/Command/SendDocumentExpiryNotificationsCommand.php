<?php

namespace App\Command;

use App\Service\Notification\DocumentExpiryNotifier;
use App\Service\Notification\WinterEquipmentNotifier;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:send-document-expiry-notifications', description: 'Sends scheduled fleet notifications.')]
class SendDocumentExpiryNotificationsCommand extends Command
{
    public function __construct(
        private readonly DocumentExpiryNotifier $documentExpiryNotifier,
        private readonly WinterEquipmentNotifier $winterEquipmentNotifier,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'date',
            null,
            InputOption::VALUE_REQUIRED,
            'Use a specific date in YYYY-MM-DD format.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = $this->resolveDate($input, $output);
        if ($today === null) {
            return Command::INVALID;
        }

        $result = $this->documentExpiryNotifier
            ->send($today)
            ->merge($this->winterEquipmentNotifier->send($today));

        $output->writeln(sprintf(
            'Emails sent: %d, emails failed: %d, notifications sent: %d, notifications failed: %d, skipped: %d',
            $result->emailsSent,
            $result->emailsFailed,
            $result->notificationsSent,
            $result->notificationsFailed,
            $result->skipped,
        ));

        return Command::SUCCESS;
    }

    private function resolveDate(InputInterface $input, OutputInterface $output): ?\DateTimeImmutable
    {
        $date = $input->getOption('date');
        if ($date === null) {
            return new \DateTimeImmutable('today');
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $date);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($parsedDate === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            $output->writeln('<error>Date must use the YYYY-MM-DD format.</error>');

            return null;
        }

        return $parsedDate;
    }
}
