<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230704043904 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if($schema->hasTable('user_profiles')) {
            if($schema->getTable('user_profiles')->hasColumn('hide_phone')) {
                $this->addSql("ALTER TABLE user_profiles CHANGE hide_phone hide_phone TINYINT(1) DEFAULT '1' NOT NULL, CHANGE hide_email hide_email TINYINT(1) DEFAULT '1' NOT NULL;");

            }
        }
    }

    public function down(Schema $schema) : void
    {
    }
}
