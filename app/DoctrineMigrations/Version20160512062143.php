<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160512062143 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */

    public function up(Schema $schema):void
    {

        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE groups_sessions_attachments (
                        id INT AUTO_INCREMENT NOT NULL, 
                        session_id INT NOT NULL, 
                        group_session_id INT NOT NULL, 
                        attachment_type VARCHAR(255) NOT NULL,
                        attachment_id INT NOT NULL,
                        publish_at DATETIME NOT NULL,
                        created_at DATETIME NOT NULL, 
                        updated_at DATETIME NOT NULL, 
                        INDEX (session_id), 
                        INDEX (group_session_id),
                        INDEX (attachment_type),
                        INDEX (attachment_id),
                        PRIMARY KEY(id))');

        $this->addSql('ALTER TABLE groups_sessions_attachments ADD CONSTRAINT FOREIGN KEY (session_id) REFERENCES sessions (id)');
        $this->addSql('ALTER TABLE groups_sessions_attachments ADD CONSTRAINT FOREIGN KEY (group_session_id) REFERENCES groups_sessions (id)');
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
