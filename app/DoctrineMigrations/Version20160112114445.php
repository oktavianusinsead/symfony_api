<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160112114445 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->hasTable('course_backups')) {
            $this->addSql('CREATE TABLE course_backups (id INT AUTO_INCREMENT NOT NULL, 
                                                        course_id INT NOT NULL, 
                                                        s3_path LONGTEXT DEFAULT NULL, 
                                                        size INT DEFAULT NULL, 
                                                        completed DATETIME DEFAULT NULL, 
                                                        in_progress TINYINT(1) NOT NULL, 
                                                        created_at DATETIME NOT NULL, 
                                                        updated_at DATETIME NOT NULL, 
                                                        UNIQUE INDEX (course_id), 
                                                        PRIMARY KEY(id))');
            $this->addSql('ALTER TABLE course_backups ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');

        }

        if(!$schema->hasTable('user_announcements')) {
            $this->addSql('CREATE TABLE user_announcements (id INT AUTO_INCREMENT NOT NULL, 
                                                        user_id INT DEFAULT NULL, 
                                                        announcement_id INT NOT NULL, 
                                                        course_id INT NOT NULL, 
                                                        created_at DATETIME NOT NULL, 
                                                        updated_at DATETIME NOT NULL, 
                                                        INDEX (user_id), 
                                                        INDEX (announcement_id), 
                                                        INDEX (course_id), 
                                                        INDEX course_user_idx (user_id, course_id), 
                                                        UNIQUE INDEX user_announcement_unique (user_id, 
                                                        announcement_id), PRIMARY KEY(id))');
            $this->addSql('ALTER TABLE user_announcements ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id)');
            $this->addSql('ALTER TABLE user_announcements ADD CONSTRAINT FOREIGN KEY (announcement_id) REFERENCES announcements (id)');
            $this->addSql('ALTER TABLE user_announcements ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');

        }

        if(!$schema->hasTable('user_documents')) {
            $this->addSql('CREATE TABLE user_documents (id INT AUTO_INCREMENT NOT NULL, 
                                                        user_id INT DEFAULT NULL, 
                                                        filedocument_id INT DEFAULT NULL, 
                                                        link_id INT DEFAULT NULL, 
                                                        linkeddocument_id INT DEFAULT NULL, 
                                                        video_id INT DEFAULT NULL, course_id INT NOT NULL, 
                                                        document_type VARCHAR(255) NOT NULL, 
                                                        created_at DATETIME NOT NULL, 
                                                        updated_at DATETIME NOT NULL, 
                                                        INDEX (user_id), 
                                                        INDEX (filedocument_id), 
                                                        INDEX (link_id), 
                                                        INDEX (linkeddocument_id), 
                                                        INDEX (video_id), 
                                                        INDEX (course_id), 
                                                        INDEX ud_user_course_idx (user_id, course_id), 
                                                        UNIQUE INDEX filedocument_user_unique (user_id, filedocument_id, document_type), 
                                                        UNIQUE INDEX link_user_unique (user_id, link_id, document_type), 
                                                        UNIQUE INDEX linkdocument_user_unique (user_id, linkeddocument_id, document_type), 
                                                        UNIQUE INDEX video_user_unique (user_id, video_id, document_type), 
                                                        PRIMARY KEY(id))');
            $this->addSql('ALTER TABLE user_documents ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id)');
            $this->addSql('ALTER TABLE user_documents ADD CONSTRAINT FOREIGN KEY (filedocument_id) REFERENCES file_documents (id)');
            $this->addSql('ALTER TABLE user_documents ADD CONSTRAINT FOREIGN KEY (link_id) REFERENCES links (id)');
            $this->addSql('ALTER TABLE user_documents ADD CONSTRAINT FOREIGN KEY (linkeddocument_id) REFERENCES linked_documents (id)');
            $this->addSql('ALTER TABLE user_documents ADD CONSTRAINT FOREIGN KEY (video_id) REFERENCES videos (id)');
            $this->addSql('ALTER TABLE user_documents ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');

        }


        if(!$schema->hasTable('user_favourites')) {
            $this->addSql('CREATE TABLE user_favourites (id INT AUTO_INCREMENT NOT NULL, 
                                                        user_id INT DEFAULT NULL, 
                                                        filedocument_id INT DEFAULT NULL, 
                                                        link_id INT DEFAULT NULL, 
                                                        linkeddocument_id INT DEFAULT NULL, 
                                                        video_id INT DEFAULT NULL, 
                                                        course_id INT NOT NULL, 
                                                        document_type VARCHAR(255) NOT NULL, 
                                                        created_at DATETIME NOT NULL, 
                                                        updated_at DATETIME NOT NULL, 
                                                        INDEX (user_id), 
                                                        INDEX (filedocument_id), 
                                                        INDEX (link_id), 
                                                        INDEX (linkeddocument_id), 
                                                        INDEX (video_id), 
                                                        INDEX (course_id), 
                                                        INDEX uf_user_course_idx (user_id, course_id), 
                                                        UNIQUE INDEX filedocument_constraint (user_id, filedocument_id, document_type), 
                                                        UNIQUE INDEX link_constraint (user_id, link_id, document_type), 
                                                        UNIQUE INDEX linkdocument_constraint (user_id, linkeddocument_id, document_type), 
                                                        UNIQUE INDEX video_constraint (user_id, video_id, document_type), 
                                                        PRIMARY KEY(id)) ');
            $this->addSql('ALTER TABLE user_favourites ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id)');
            $this->addSql('ALTER TABLE user_favourites ADD CONSTRAINT FOREIGN KEY (filedocument_id) REFERENCES file_documents (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_favourites ADD CONSTRAINT FOREIGN KEY (link_id) REFERENCES links (id)');
            $this->addSql('ALTER TABLE user_favourites ADD CONSTRAINT FOREIGN KEY (linkeddocument_id) REFERENCES linked_documents (id)');
            $this->addSql('ALTER TABLE user_favourites ADD CONSTRAINT FOREIGN KEY (video_id) REFERENCES videos (id)');
            $this->addSql('ALTER TABLE user_favourites ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');

        }

        if(!$schema->hasTable('user_sso_state')) {
            $this->addSql('CREATE TABLE user_sso_state (id INT AUTO_INCREMENT NOT NULL, 
                                                        PRIMARY KEY(id))');
        }

        if(!$schema->hasTable('user_subtasks')) {
        $this->addSql('CREATE TABLE user_subtasks (id INT AUTO_INCREMENT NOT NULL, 
                                                    user_id INT DEFAULT NULL, 
                                                    subtask_id INT NOT NULL, 
                                                    course_id INT NOT NULL, 
                                                    created_at DATETIME NOT NULL, 
                                                    updated_at DATETIME NOT NULL, 
                                                    INDEX (user_id), 
                                                    INDEX (subtask_id), 
                                                    INDEX (course_id), 
                                                    INDEX us_user_course_idx (user_id, course_id), 
                                                    UNIQUE INDEX user_subtask_unique (user_id, subtask_id), 
                                                    PRIMARY KEY(id))');
            $this->addSql('ALTER TABLE user_subtasks ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id)');
            $this->addSql('ALTER TABLE user_subtasks ADD CONSTRAINT FOREIGN KEY (subtask_id) REFERENCES subtasks (id)');
            $this->addSql('ALTER TABLE user_subtasks ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');

        }


        if(!$schema->hasTable('saml_sso_state')) {
            $this->addSql('CREATE TABLE saml_sso_state (id INT AUTO_INCREMENT NOT NULL, 
                                                        PROVIDER_ID VARCHAR(32) NOT NULL, 
                                                        AUTH_SVC_NAME VARCHAR(32) NOT NULL, 
                                                        SESSION_INDEX VARCHAR(64) DEFAULT NULL, 
                                                        NAME_ID VARCHAR(64) NOT NULL, 
                                                        NAME_ID_FORMAT VARCHAR(64) NOT NULL, 
                                                        CREATED_ON DATETIME NOT NULL, 
                                                        saml_response_raw LONGTEXT NOT NULL, PRIMARY KEY(id))');
        }

        if(!$schema->getTable('file_documents')->hasColumn('filesize')) {
            $this->addSql('ALTER TABLE file_documents ADD filesize INT DEFAULT NULL');
        }

        if(!$schema->getTable('file_documents')->hasColumn('pages')) {
            $this->addSql('ALTER TABLE file_documents ADD pages INT DEFAULT NULL');
        }

        if(!$schema->getTable('subtasks')->hasColumn('filesize')) {
            $this->addSql('ALTER TABLE subtasks ADD filesize INT DEFAULT NULL');
        }

        if(!$schema->getTable('subtasks')->hasColumn('filename')) {
            $this->addSql('ALTER TABLE subtasks ADD filename VARCHAR(255) DEFAULT NULL');
        }

        if(!$schema->getTable('subtasks')->hasColumn('mime_type')) {
            $this->addSql('ALTER TABLE subtasks ADD mime_type VARCHAR(255) DEFAULT NULL');
        }

        if(!$schema->getTable('subtasks')->hasColumn('pages')) {
            $this->addSql('ALTER TABLE subtasks ADD pages INT DEFAULT NULL');
        }

        if(!$schema->getTable('users')->hasColumn('agreement')) {
            $this->addSql('ALTER TABLE users ADD agreement TINYINT(1) DEFAULT \'0\' DEFAULT NULL');
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
