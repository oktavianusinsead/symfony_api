<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190214144104 extends AbstractMigration
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
            if(!$schema->getTable('vanillaprogrammegroup')->hasColumn('groupDescription')) {
                $this->addSql('ALTER TABLE vanillaprogrammegroup ADD groupDescription VARCHAR(255) DEFAULT NULL;');
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
