<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220522222347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE code ADD polling_id INT NOT NULL');
        $this->addSql('ALTER TABLE code ADD CONSTRAINT FK_771530986B4374D8 FOREIGN KEY (polling_id) REFERENCES polling (id)');
        $this->addSql('CREATE INDEX IDX_771530986B4374D8 ON code (polling_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE code DROP FOREIGN KEY FK_771530986B4374D8');
        $this->addSql('DROP INDEX IDX_771530986B4374D8 ON code');
        $this->addSql('ALTER TABLE code DROP polling_id');
    }
}
