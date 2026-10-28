<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261028210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track user last activity and one activity record per user per day.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD last_active_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_activity (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, company_id INT DEFAULT NULL, activity_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', last_seen_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_USER_ACTIVITY_USER (user_id), INDEX IDX_USER_ACTIVITY_COMPANY (company_id), UNIQUE INDEX UNIQ_USER_ACTIVITY_DAY (user_id, activity_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_activity ADD CONSTRAINT FK_USER_ACTIVITY_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_activity ADD CONSTRAINT FK_USER_ACTIVITY_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_activity');
        $this->addSql('ALTER TABLE app_user DROP last_active_at');
    }
}
