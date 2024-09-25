<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171208082817 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->hasTable('admin_session_location')) {
            $this->addSql(
                'CREATE TABLE admin_session_location 
                (
                  id INT AUTO_INCREMENT NOT NULL, 
                  course_id INT NOT NULL, 
                  user_id INT NOT NULL, 
                  location VARCHAR(255) NOT NULL, 
                  created_at DATETIME NOT NULL, 
                  updated_at DATETIME NOT NULL, 
                  INDEX admin_location_course_idx (course_id), 
                  INDEX admin_location_user_idx (user_id), 
                  PRIMARY KEY(id)
                )'
            );

            $this->addSql('ALTER TABLE admin_session_location ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');
            $this->addSql('ALTER TABLE admin_session_location ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id);');
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
