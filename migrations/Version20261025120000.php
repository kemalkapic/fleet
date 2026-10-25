<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event key for deduplicating scheduled system notifications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification ADD event_key VARCHAR(100) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_NOTIFICATION_EVENT_LOOKUP ON notification (company_id, event_key, channel, recipient, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_NOTIFICATION_EVENT_LOOKUP ON notification');
        $this->addSql('ALTER TABLE notification DROP event_key');
    }
}
