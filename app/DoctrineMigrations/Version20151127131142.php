<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151127131142 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sessions CHANGE start_date slot_start DATETIME');
        $this->addSql('ALTER TABLE sessions CHANGE end_date slot_end DATETIME');

        $this->addSql('ALTER TABLE activities CHANGE start_date slot_start DATETIME');
        $this->addSql('ALTER TABLE activities CHANGE end_date slot_end DATETIME');

        if($schema->getTable('courses')->hasColumn('welcome')) {
            $this->addSql('ALTER TABLE courses DROP welcome');
        }
        
        if($schema->getTable('courses')->hasColumn('link_webmail')) {
            $this->addSql('ALTER TABLE courses DROP link_webmail');
        }

        if($schema->getTable('courses')->hasColumn('link_yammer')) {
            $this->addSql('ALTER TABLE courses DROP link_yammer');
        }

        if($schema->getTable('courses')->hasColumn('link_myinsead')) {
            $this->addSql('ALTER TABLE courses DROP link_myinsead');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
