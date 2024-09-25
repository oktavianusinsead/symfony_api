<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181112070650 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->hasTable('archived_user_tokens')) {
            $this->addSql(
                'CREATE TABLE archived_user_tokens
                (
                  id INT AUTO_INCREMENT NOT NULL, 
                  user_id INT NOT NULL, 
                  scope VARCHAR(255) NOT NULL, 
                  refreshable TINYINT(1) NOT NULL, 
                  created_at DATETIME NOT NULL, 
                  updated_at DATETIME NOT NULL,
                  INDEX archived_user_token_user_idx (user_id), 
                  PRIMARY KEY(id)
                )'
            );

            $this->addSql('ALTER TABLE archived_user_tokens ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id);');
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
