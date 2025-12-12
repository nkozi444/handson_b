<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212042349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tour CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_6AD1F969A76ED395 ON tour (user_id)');
        $this->addSql('CREATE INDEX idx_tour_date ON tour (date)');
        $this->addSql('CREATE INDEX idx_tour_status ON tour (status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tour DROP FOREIGN KEY FK_6AD1F969A76ED395');
        $this->addSql('DROP INDEX IDX_6AD1F969A76ED395 ON tour');
        $this->addSql('DROP INDEX idx_tour_date ON tour');
        $this->addSql('DROP INDEX idx_tour_status ON tour');
        $this->addSql('ALTER TABLE tour CHANGE user_id user_id INT NOT NULL');
    }
}
