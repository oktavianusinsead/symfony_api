<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190326104643 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @throws \Doctrine\Migrations\AbortMigrationException
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if(!$schema->hasTable('vanillaconversation')) {
            $this->addSql("CREATE TABLE vanillaconversation (id INT AUTO_INCREMENT NOT NULL, programme INT DEFAULT NULL, user INT DEFAULT NULL, userList VARCHAR(255) NOT NULL, conversationID INT DEFAULT NULL, isProcessed TINYINT(1) DEFAULT '0' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_62A520643DDCB9FF (programme), INDEX IDX_62A520648D93D649 (user), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $this->addSql("ALTER TABLE vanillaconversation ADD CONSTRAINT FK_62A520643DDCB9FF FOREIGN KEY (programme) REFERENCES programmes (id);");
            $this->addSql("ALTER TABLE vanillaconversation ADD CONSTRAINT FK_62A520648D93D649 FOREIGN KEY (user) REFERENCES users (id);");
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\Migrations\AbortMigrationException
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
