<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171211055759 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->hasTable('template_tasks')) {
            $this->addSql(
                'CREATE TABLE template_tasks
                (
                    id INT AUTO_INCREMENT NOT NULL, 
                    title VARCHAR(255) NOT NULL, 
                    description LONGTEXT NOT NULL, 
                    box_folder_id VARCHAR(255) DEFAULT NULL, 
                    created_at DATETIME NOT NULL, 
                    updated_at DATETIME NOT NULL, 
                    PRIMARY KEY(id)
                )'
            );

        }

        if(!$schema->hasTable('template_subtasks')) {
            $this->addSql(
                'CREATE TABLE template_subtasks
                (
                    id INT AUTO_INCREMENT NOT NULL, 
                    task_id INT NOT NULL, 
                    title VARCHAR(255) NOT NULL, 
                    subtask_type INT NOT NULL, 
                    url LONGTEXT DEFAULT NULL, 
                    filesize INT DEFAULT NULL, 
                    filename VARCHAR(255) DEFAULT NULL, 
                    mime_type VARCHAR(255) DEFAULT NULL, 
                    pages INT DEFAULT NULL, 
                    box_id VARCHAR(255) DEFAULT NULL, 
                    created_at DATETIME NOT NULL, 
                    updated_at DATETIME NOT NULL, 
                    INDEX template_task_idx (task_id), 
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
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
