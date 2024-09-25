<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170509062115 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        //change programme_id as PK
        $this->addSql('ALTER TABLE courses_users_role DROP FOREIGN KEY `courses_users_role_ibfk_1`');

        $this->addSql('ALTER TABLE courses_users_role CHANGE COLUMN `programme_id` `programme_id` INT(11) NOT NULL COMMENT \'\'');
        $this->addSql('ALTER TABLE courses_users_role DROP PRIMARY KEY, ADD PRIMARY KEY (`id`, `user_id`, `programme_id`)  COMMENT \'\'');

        $this->addSql('ALTER TABLE courses_users_role ADD INDEX `courses_users_ibfk_1_idx` (`programme_id` ASC, `user_id` ASC)  COMMENT \'\'');

        $this->addSql('CREATE TABLE programmes_users (
                        id INT AUTO_INCREMENT NOT NULL,
                        programme_id INT NOT NULL,
                        user_id INT NOT NULL,
                        row_index INT NOT NULL,
                        order_index INT NOT NULL,
                        created_at DATETIME NOT NULL,
                        updated_at DATETIME NOT NULL,
                        INDEX (programme_id),
                        INDEX (user_id),
                        PRIMARY KEY(id))');

        $this->addSql('ALTER TABLE programmes_users ADD CONSTRAINT FOREIGN KEY (`programme_id`,`user_id`) REFERENCES courses_users_role (`programme_id`,`user_id`)');

        $this->addSql('ALTER TABLE courses_users_role ADD CONSTRAINT FOREIGN KEY (`programme_id`) REFERENCES programmes (`id`)');
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
