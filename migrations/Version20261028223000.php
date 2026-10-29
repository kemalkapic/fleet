<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261028223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the accepted terms version and acceptance time for each user.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE app_user ADD terms_accepted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD terms_accepted_version VARCHAR(30) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP terms_accepted_at, DROP terms_accepted_version');
    }
}
