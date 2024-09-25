<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230201151920 extends AbstractMigration
{
   

    public function up(Schema $schema) : void
    {
       

        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if(!$schema->hasTable('learning_journey')) {
            $this->addSql("
                  CREATE TABLE IF NOT EXISTS learning_journey (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `course_id` INT NOT NULL,
                  `title` VARCHAR(255) NOT NULL,
                  `description` LONGTEXT NULL,
                  `published` TINYINT(1) NOT NULL,
                `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `image_path` LONGTEXT,
                  PRIMARY KEY (`id`));");

        }

    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');


    }
}
