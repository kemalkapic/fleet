<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an optional logo to each company.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company ADD logo_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company DROP logo_path');
    }
}
