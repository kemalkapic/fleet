<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261127140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vehicle expense records.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE vehicle_expense (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, vehicle_id INT NOT NULL, expense_date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', type VARCHAR(40) NOT NULL, amount_bam NUMERIC(10, 2) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_DA059F0B979B1AD6 (company_id), INDEX IDX_DA059F0B545317D1 (vehicle_id), INDEX vehicle_expense_date_idx (expense_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE vehicle_expense ADD CONSTRAINT FK_DA059F0B979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicle_expense ADD CONSTRAINT FK_DA059F0B545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vehicle_expense');
    }
}
