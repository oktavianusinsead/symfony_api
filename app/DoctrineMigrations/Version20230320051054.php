<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230320051054 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        if($schema->hasTable('courses')) {
            if(!$schema->getTable('courses')->hasColumn('course_type_view')) {
                $this->addSql("ALTER TABLE courses ADD course_type_view INT DEFAULT '0' NOT NULL;");
                $this->addSql("update courses c INNER JOIN programmes p on c.programme_id = p.id  set c.course_type_view = p.view_type where p.view_type < 3;");
               
            }
        }


    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
