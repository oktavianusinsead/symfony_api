<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200511031812 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if($schema->hasTable('file_documents')) {
            if (!$schema->getTable('file_documents')->hasColumn('is_upload_to_s3')) {
                $this->addSql('ALTER TABLE file_documents ADD is_upload_to_s3 INT DEFAULT 0');
            }
            if (!$schema->getTable('file_documents')->hasColumn('aws_path')) {
                $this->addSql('ALTER TABLE file_documents ADD aws_path LONGTEXT DEFAULT NULL');
            }
            if (!$schema->getTable('file_documents')->hasColumn('file_id')) {
                $this->addSql('ALTER TABLE file_documents ADD file_id VARCHAR(255) DEFAULT NULL');
            }
        }
        if($schema->hasTable('subtasks')) {
            if (!$schema->getTable('subtasks')->hasColumn('is_upload_to_s3')) {
                $this->addSql('ALTER TABLE subtasks ADD is_upload_to_s3 INT DEFAULT 0');
            }
            if (!$schema->getTable('subtasks')->hasColumn('aws_path')) {
                $this->addSql('ALTER TABLE subtasks ADD aws_path LONGTEXT DEFAULT NULL');
            }
            if (!$schema->getTable('subtasks')->hasColumn('file_id')) {
                $this->addSql('ALTER TABLE subtasks ADD file_id VARCHAR(255) DEFAULT NULL');
            }
        }

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE file_documents DROP is_upload_to_s3, DROP aws_path, DROP file_id');
        $this->addSql('ALTER TABLE subtasks DROP is_upload_to_s3, DROP aws_path, DROP file_id');
    }
}
