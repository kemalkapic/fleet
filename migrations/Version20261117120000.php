<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261117120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create vehicle service book records and receipt attachments.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vehicle_service_record (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, vehicle_id INT NOT NULL, type VARCHAR(30) NOT NULL, service_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', odometer_km INT NOT NULL, service_provider VARCHAR(180) DEFAULT NULL, cost_bam NUMERIC(10, 2) DEFAULT NULL, items JSON NOT NULL, additional_work LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_VEHICLE_SERVICE_COMPANY (company_id), INDEX IDX_VEHICLE_SERVICE_VEHICLE (vehicle_id), INDEX IDX_VEHICLE_SERVICE_TIMELINE (vehicle_id, service_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vehicle_service_attachment (id INT AUTO_INCREMENT NOT NULL, service_record_id INT NOT NULL, storage_key VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size_bytes INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_SERVICE_ATTACHMENT_RECORD (service_record_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vehicle_service_record ADD CONSTRAINT FK_VEHICLE_SERVICE_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicle_service_record ADD CONSTRAINT FK_VEHICLE_SERVICE_VEHICLE FOREIGN KEY (vehicle_id) REFERENCES vehicle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicle_service_attachment ADD CONSTRAINT FK_SERVICE_ATTACHMENT_RECORD FOREIGN KEY (service_record_id) REFERENCES vehicle_service_record (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vehicle_service_attachment');
        $this->addSql('DROP TABLE vehicle_service_record');
    }
}
