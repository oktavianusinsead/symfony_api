<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180207033632 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->hasTable('programme_administrator')) {
            $this->addSql(
                'CREATE TABLE programme_administrator
                (
                  id INT AUTO_INCREMENT NOT NULL, 
                  programme_id INT DEFAULT NULL, 
                  user_id INT NOT NULL, 
                  is_owner TINYINT(1) NOT NULL, 
                  INDEX programme_administrator_prog_idx (programme_id), 
                  INDEX programme_administrator_user_idx (user_id), 
                  UNIQUE INDEX programme_administrator_unique (programme_id, user_id), 
                  PRIMARY KEY(id)
                )'
            );

            $this->addSql('ALTER TABLE programme_administrator ADD CONSTRAINT FOREIGN KEY (programme_id) REFERENCES programmes (id);');
            $this->addSql('ALTER TABLE programme_administrator ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id);');
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
