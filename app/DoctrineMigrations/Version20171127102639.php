<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171127102639 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->hasTable('administrators')) {
            $this->addSql(
                'CREATE TABLE administrators 
                (
                  id INT AUTO_INCREMENT NOT NULL, 
                  peoplesoft_id VARCHAR(255) NOT NULL, 
                  last_login DATETIME NOT NULL, 
                  created_at DATETIME NOT NULL, 
                  updated_at DATETIME NOT NULL, 
                  UNIQUE INDEX admin_psoft_unique (peoplesoft_id), 
                  INDEX administrator_idx (peoplesoft_id), 
                  PRIMARY KEY(id)
                )'
            );

        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
