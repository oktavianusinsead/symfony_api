<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210215082830 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if($schema->hasTable('template_subtasks')) {
            if(!$schema->getTable('template_subtasks')->hasColumn('embedded_content')) {
                $this->addSql("ALTER TABLE template_subtasks ADD embedded_content LONGTEXT DEFAULT NULL;");
            }
        }

        if($schema->hasTable('subtasks')) {
            if (!$schema->getTable('subtasks')->hasColumn('embedded_content')) {
                $this->addSql("ALTER TABLE subtasks ADD embedded_content LONGTEXT DEFAULT NULL;");
            }
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
