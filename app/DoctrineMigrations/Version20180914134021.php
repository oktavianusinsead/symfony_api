<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180914134021 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if($schema->hasTable('admin_session_location')) {
            $this->addSql( 'ALTER TABLE admin_session_location CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('groups_activities')) {
            $this->addSql( 'ALTER TABLE groups_activities CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('groups_sessions')) {
            $this->addSql( 'ALTER TABLE groups_sessions CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
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
