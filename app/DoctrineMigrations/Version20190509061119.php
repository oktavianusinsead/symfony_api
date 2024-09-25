<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190509061119 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\Migrations\AbortMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if($schema->hasTable('tasks')) {
            if(!$schema->getTable('tasks')->hasColumn('isHighPriority')){
                $this->addSql("ALTER TABLE tasks ADD isHighPriority TINYINT(1) DEFAULT NULL, CHANGE date date DATETIME DEFAULT NULL;");
            }
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\Migrations\AbortMigrationException
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
