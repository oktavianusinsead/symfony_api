<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180614042307 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->hasTable('pending_attachments')) {
            $this->addSql(
                'CREATE TABLE pending_attachments (
                id INT AUTO_INCREMENT NOT NULL, 
                session_id INT NOT NULL, 
                attachment_id INT NOT NULL, 
                attachment_type VARCHAR(255) NOT NULL, 
                publish_at DATETIME NOT NULL, 
                published TINYINT(1) NOT NULL, 
                created_at DATETIME NOT NULL, 
                updated_at DATETIME NOT NULL, 
                INDEX pending_attachment_session_idx (session_id), 
                PRIMARY KEY(id)
            )');

            $this->addSql('ALTER TABLE pending_attachments ADD CONSTRAINT FOREIGN KEY (session_id) REFERENCES sessions (id)');
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
