<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210518024508 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');


        if($schema->hasTable('courses')) {
            if(!$schema->getTable('courses')->hasColumn('original_country')) {
                $this->addSql("ALTER TABLE courses ADD original_country VARCHAR(255) NOT NULL, ADD original_timezone VARCHAR(255) NOT NULL;");
                $this->addSql("UPDATE courses set original_country = country, original_timezone = timezone");
            }
        }

        if($schema->hasTable('groups_sessions')) {
            if(!$schema->getTable('groups_sessions')->hasColumn('original_start_date')) {
                $this->addSql("ALTER TABLE groups_sessions ADD original_start_date DATETIME NOT NULL, ADD original_end_date DATETIME NOT NULL;");
                $this->addSql("UPDATE groups_sessions set original_start_date = start_date, original_end_date = end_date");
            }
        }

        if($schema->hasTable('groups_activities')) {
            if(!$schema->getTable('groups_activities')->hasColumn('original_start_date')) {
                $this->addSql("ALTER TABLE groups_activities ADD original_start_date DATETIME NOT NULL, ADD original_end_date DATETIME NOT NULL;");
                $this->addSql("UPDATE groups_activities set original_start_date = start_date, original_end_date = end_date");
            }
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
