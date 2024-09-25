<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190214060746 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws
     */
    public function up(Schema $schema):void

    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if($schema->hasTable('vanillaprogrammegroup')) {
            if(!$schema->getTable('vanillaprogrammegroup')->hasColumn('isInitial')) {
                $this->addSql('ALTER TABLE vanillaprogrammegroup ADD isInitial TINYINT(1) DEFAULT \'0\' NOT NULL;');
            }

            if(!$schema->getTable('vanillaprogrammegroup')->hasIndex('IDX_4EEBADAB62BB7AEE')) {
                $this->addSql('CREATE INDEX IDX_4EEBADAB62BB7AEE ON vanillaprogrammegroup (programme_id);');
            }
        }
    }

    /**
     * @param Schema $schema
     * @throws
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
