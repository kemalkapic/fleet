<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261127090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional installation date to vehicle tires.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE vehicle_tire ADD mounted_at DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicle_tire DROP mounted_at');
    }
}
