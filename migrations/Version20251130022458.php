<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251130022458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

 public function up(Schema $schema): void
{
    // 1. Add column as NULLable temporarily
    $this->addSql('ALTER TABLE `user` ADD created_at DATETIME NULL');

    // 2. Set a default datetime for existing rows
    $this->addSql("UPDATE `user` SET created_at = NOW() WHERE created_at IS NULL");

    // 3. Now make column NOT NULL
    $this->addSql('ALTER TABLE `user` MODIFY created_at DATETIME NOT NULL');
}

    public function down(Schema $schema): void
{
    $this->addSql('ALTER TABLE `user` DROP created_at');
}
}
