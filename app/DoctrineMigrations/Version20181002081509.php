<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181002081509 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if($schema->hasTable('administrators')) {
            $this->addSql( 'ALTER TABLE administrators CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('course_backup_emails')) {
            $this->addSql( 'ALTER TABLE course_backup_emails CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('course_backups')) {
            $this->addSql( 'ALTER TABLE course_backups CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('groups')) {
            $this->addSql( 'ALTER TABLE groups CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('groups_sessions_attachments')) {
            $this->addSql( 'ALTER TABLE groups_sessions_attachments CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('groups_users')) {
            $this->addSql( 'ALTER TABLE groups_users CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('pending_attachments')) {
            $this->addSql( 'ALTER TABLE pending_attachments CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('programme_administrator')) {
            $this->addSql( 'ALTER TABLE programme_administrator CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('programmes_users')) {
            $this->addSql( 'ALTER TABLE programmes_users CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('saml_sso_state')) {
            $this->addSql( 'ALTER TABLE saml_sso_state CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('template_subtasks')) {
            $this->addSql( 'ALTER TABLE template_subtasks CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('template_tasks')) {
            $this->addSql( 'ALTER TABLE template_tasks CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('user_announcements')) {
            $this->addSql( 'ALTER TABLE user_announcements CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('user_documents')) {
            $this->addSql( 'ALTER TABLE user_documents CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('user_favourites')) {
            $this->addSql( 'ALTER TABLE user_favourites CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('user_sso_state')) {
            $this->addSql( 'ALTER TABLE user_sso_state CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
        }

        if($schema->hasTable('user_subtasks')) {
            $this->addSql( 'ALTER TABLE user_subtasks CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci' );
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
