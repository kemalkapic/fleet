<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user notification inbox, browser push subscriptions and push delivery links.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_notification (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, user_id INT NOT NULL, document_id INT DEFAULT NULL, event_key VARCHAR(191) NOT NULL, title VARCHAR(255) NOT NULL, message_text LONGTEXT NOT NULL, target_url VARCHAR(500) DEFAULT NULL, read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_USER_NOTIFICATION_COMPANY (company_id), INDEX IDX_USER_NOTIFICATION_USER (user_id), INDEX IDX_USER_NOTIFICATION_DOCUMENT (document_id), UNIQUE INDEX UNIQ_USER_NOTIFICATION_EVENT (user_id, event_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE push_subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, endpoint LONGTEXT NOT NULL, endpoint_hash VARCHAR(64) NOT NULL, public_key LONGTEXT NOT NULL, auth_token LONGTEXT NOT NULL, content_encoding VARCHAR(30) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, revoked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_PUSH_SUBSCRIPTION_USER (user_id), UNIQUE INDEX UNIQ_PUSH_ENDPOINT_HASH (endpoint_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_notification ADD CONSTRAINT FK_USER_NOTIFICATION_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_notification ADD CONSTRAINT FK_USER_NOTIFICATION_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_notification ADD CONSTRAINT FK_USER_NOTIFICATION_DOCUMENT FOREIGN KEY (document_id) REFERENCES fleet_document (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE push_subscription ADD CONSTRAINT FK_PUSH_SUBSCRIPTION_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD inbox_item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_NOTIFICATION_INBOX_ITEM FOREIGN KEY (inbox_item_id) REFERENCES user_notification (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_NOTIFICATION_INBOX_ITEM ON notification (inbox_item_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_NOTIFICATION_INBOX_ITEM');
        $this->addSql('DROP INDEX IDX_NOTIFICATION_INBOX_ITEM ON notification');
        $this->addSql('ALTER TABLE notification DROP inbox_item_id');
        $this->addSql('DROP TABLE push_subscription');
        $this->addSql('DROP TABLE user_notification');
    }
}
