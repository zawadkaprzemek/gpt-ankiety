<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220511222739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE answer (id INT AUTO_INCREMENT NOT NULL, question_id INT NOT NULL, content VARCHAR(255) NOT NULL, INDEX IDX_DADD4A251E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE code (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, content VARCHAR(255) NOT NULL, multi TINYINT(1) NOT NULL, INDEX IDX_77153098A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page (id INT AUTO_INCREMENT NOT NULL, polling_id INT NOT NULL, number INT NOT NULL, INDEX IDX_140AB6206B4374D8 (polling_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE polling (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, text_content VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_CA3A2250A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, polling_id INT NOT NULL, page_id INT NOT NULL, content VARCHAR(255) NOT NULL, sort INT NOT NULL, required TINYINT(1) NOT NULL, comment VARCHAR(255) DEFAULT NULL, min_val_text VARCHAR(255) DEFAULT NULL, middle_val_text VARCHAR(255) DEFAULT NULL, max_val_text VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B6F7494EC54C8C93 (type_id), INDEX IDX_B6F7494E6B4374D8 (polling_id), INDEX IDX_B6F7494EC4663E4 (page_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session_user (id INT AUTO_INCREMENT NOT NULL, code_id INT NOT NULL, ip_address VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_4BE2D66327DAFE17 (code_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vote (id INT AUTO_INCREMENT NOT NULL, session_user_id INT NOT NULL, question_id INT NOT NULL, answer LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_5A108564B5B651CF (session_user_id), INDEX IDX_5A1085641E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A251E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE code ADD CONSTRAINT FK_77153098A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB6206B4374D8 FOREIGN KEY (polling_id) REFERENCES polling (id)');
        $this->addSql('ALTER TABLE polling ADD CONSTRAINT FK_CA3A2250A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494EC54C8C93 FOREIGN KEY (type_id) REFERENCES question_type (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E6B4374D8 FOREIGN KEY (polling_id) REFERENCES polling (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494EC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE session_user ADD CONSTRAINT FK_4BE2D66327DAFE17 FOREIGN KEY (code_id) REFERENCES code (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564B5B651CF FOREIGN KEY (session_user_id) REFERENCES session_user (id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A1085641E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE session_user DROP FOREIGN KEY FK_4BE2D66327DAFE17');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EC4663E4');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB6206B4374D8');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E6B4374D8');
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A251E27F6BF');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A1085641E27F6BF');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564B5B651CF');
        $this->addSql('DROP TABLE answer');
        $this->addSql('DROP TABLE code');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE polling');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE session_user');
        $this->addSql('DROP TABLE vote');
    }
}
