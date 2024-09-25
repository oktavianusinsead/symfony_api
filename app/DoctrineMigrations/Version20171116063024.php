<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171116063024 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->getTable('programmes')->hasColumn('book_timestamp_full')) {
            $this->addSql('ALTER TABLE programmes ADD book_timestamp_full DATETIME DEFAULT NULL;');
        }
        if(!$schema->getTable('programmes')->hasColumn('book_timestamp_business')) {
            $this->addSql('ALTER TABLE programmes ADD book_timestamp_business DATETIME DEFAULT NULL;');
        }
        if(!$schema->getTable('users')->hasColumn('profile_last_updated')) {
            $this->addSql('ALTER TABLE users ADD profile_last_updated DATETIME DEFAULT NULL;');
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
