<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240219075754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if($schema->hasTable('programmes')) {
            if($schema->getTable('programmes')->hasColumn('link_learninghub')) {
                $this->addSql("ALTER TABLE programmes CHANGE link_learninghub link_learninghub TINYINT(1) DEFAULT 1;");
                $this->addSql("UPDATE programmes set link_learninghub=0;");
            }
        }

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
