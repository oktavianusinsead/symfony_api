<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151214121912 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->skipIf($schema->hasTable('course_backup_emails'), 'Schema already modified!');
        
        $this->addSql('CREATE TABLE course_backup_emails (
                        id INT AUTO_INCREMENT NOT NULL, 
                        course_id INT NOT NULL, 
                        user_id INT NOT NULL, 
                        created_at DATETIME NOT NULL, 
                        updated_at DATETIME NOT NULL, 
                        INDEX (course_id), 
                        INDEX (user_id), 
                        UNIQUE INDEX course_backup_email_unique (course_id, user_id), 
                        PRIMARY KEY(id))');

        $this->addSql('ALTER TABLE course_backup_emails ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');
        $this->addSql('ALTER TABLE course_backup_emails ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
