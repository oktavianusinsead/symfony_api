<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200903012034 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if($schema->hasTable('sessions')) {
            if (!$schema->getTable('sessions')->hasColumn('is_scheduled'))
                $this->addSql("ALTER TABLE sessions CHANGE is_scheduled is_scheduled TINYINT(1) DEFAULT '0' NOT NULL;");
        }

        if($schema->hasTable('activities')) {
            if (!$schema->getTable('activities')->hasColumn('is_scheduled'))
                $this->addSql("ALTER TABLE activities ADD is_scheduled TINYINT(1) DEFAULT '0' NOT NULL;");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
