<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190128093121 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void

    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if(!$schema->hasTable('vanillaprogrammegroup')) {
            $this->addSql('CREATE TABLE vanillaprogrammegroup (id INT AUTO_INCREMENT NOT NULL, programme_id INT DEFAULT NULL, vanilla_group_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_4EEBADAB62BB7AEE (programme_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
            $this->addSql('ALTER TABLE vanillaprogrammegroup ADD CONSTRAINT FK_4EEBADAB62BB7AEE FOREIGN KEY (programme_id) REFERENCES programmes (id);');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
