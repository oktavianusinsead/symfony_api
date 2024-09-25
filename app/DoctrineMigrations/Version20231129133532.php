<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231129133532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if($schema->hasTable('learning_journey')) {
            if(!$schema->getTable('learning_journey')->hasColumn('created_at')) {
                $this->addSql("ALTER TABLE learning_journey ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, DROP image_path, CHANGE description description LONGTEXT NOT NULL, CHANGE published_at published_at DATETIME DEFAULT NULL;");
            }
            if($schema->getTable('learning_journey')->hasColumn('course_id')) {
                $this->addSql("ALTER TABLE learning_journey ADD CONSTRAINT FK_CB17E51591CC992 FOREIGN KEY(course_id) REFERENCES courses(id)");
                $this->addSql("CREATE INDEX IDX_CB17E51591CC992 ON learning_journey (course_id)");
            }
        }

        if($schema->hasTable('programmes')) {
            $this->addSql("ALTER TABLE programmes CHANGE view_type view_type INT default 3 NOT NULL;");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
