<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151126131031 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE groups (
                        id INT AUTO_INCREMENT NOT NULL, 
                        course_id INT NOT NULL, name VARCHAR(255) NOT NULL, 
                        start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, 
                        ps_stdnt_group VARCHAR(255) DEFAULT NULL, 
                        ps_descr VARCHAR(255) DEFAULT NULL, 
                        created_at DATETIME NOT NULL, 
                        updated_at DATETIME NOT NULL, 
                        INDEX (course_id), 
                        PRIMARY KEY(id))');

        $this->addSql('CREATE TABLE groups_users (
                        group_id INT NOT NULL, 
                        user_id INT NOT NULL, 
                        INDEX (group_id), 
                        INDEX (user_id), 
                        PRIMARY KEY(group_id, user_id))');

        $this->addSql('CREATE TABLE groups_activities (
                        id INT AUTO_INCREMENT NOT NULL, 
                        activity_id INT NOT NULL, 
                        group_id INT NOT NULL, 
                        start_date DATETIME NOT NULL, 
                        end_date DATETIME NOT NULL, 
                        location VARCHAR(255) NOT NULL, 
                        published TINYINT(1) NOT NULL, 
                        created_at DATETIME NOT NULL, 
                        updated_at DATETIME NOT NULL, 
                        INDEX (activity_id), 
                        INDEX (group_id), 
                        PRIMARY KEY(id))');

        $this->addSql('CREATE TABLE groups_sessions (
                        id INT AUTO_INCREMENT NOT NULL, 
                        session_id INT NOT NULL, 
                        group_id INT NOT NULL, 
                        start_date DATETIME NOT NULL, 
                        end_date DATETIME NOT NULL, 
                        location VARCHAR(255) NOT NULL, 
                        published TINYINT(1) NOT NULL, 
                        created_at DATETIME NOT NULL, 
                        updated_at DATETIME NOT NULL, 
                        INDEX (session_id), 
                        INDEX (group_id), 
                        PRIMARY KEY(id))');

        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');
        $this->addSql('ALTER TABLE groups_users ADD CONSTRAINT FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groups_users ADD CONSTRAINT FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groups_activities ADD CONSTRAINT FOREIGN KEY (activity_id) REFERENCES activities (id)');
        $this->addSql('ALTER TABLE groups_activities ADD CONSTRAINT FOREIGN KEY (group_id) REFERENCES groups (id)');
        $this->addSql('ALTER TABLE groups_sessions ADD CONSTRAINT FOREIGN KEY (session_id) REFERENCES sessions (id)');
        $this->addSql('ALTER TABLE groups_sessions ADD CONSTRAINT FOREIGN KEY (group_id) REFERENCES groups (id)');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups_users DROP FOREIGN KEY FK_4520C24DFE54D947');
        $this->addSql('ALTER TABLE groups_activities DROP FOREIGN KEY FK_EE39D625FE54D947');
        $this->addSql('ALTER TABLE groups_sessions DROP FOREIGN KEY FK_217267CCFE54D947');
        $this->addSql('DROP TABLE groups');
        $this->addSql('DROP TABLE groups_users');
        $this->addSql('DROP TABLE groups_activities');
        $this->addSql('DROP TABLE groups_sessions');
        $this->addSql('ALTER TABLE courses ADD dtype VARCHAR(255) NOT NULL, DROP ps_class_section');
    }
}
