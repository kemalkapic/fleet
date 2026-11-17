<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261117143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email activation fields for public end-user registration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE app_user ADD email_verified_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD activation_token_hash VARCHAR(64) DEFAULT NULL, ADD activation_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE INDEX IDX_USER_ACTIVATION_TOKEN ON app_user (activation_token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_USER_ACTIVATION_TOKEN ON app_user');
        $this->addSql('ALTER TABLE app_user DROP email_verified_at, DROP activation_token_hash, DROP activation_expires_at');
    }
}
