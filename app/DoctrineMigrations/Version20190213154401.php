<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190213154401 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if($schema->hasTable('vanillausergroup')) {
            $this->addSql('ALTER TABLE vanillausergroup ADD user_id INT NOT NULL, ADD group_id INT NOT NULL, ADD isAdded TINYINT(1) DEFAULT \'0\' NOT NULL, ADD isRemove TINYINT(1) DEFAULT \'0\' NOT NULL;');
            $this->addSql('ALTER TABLE vanillausergroup ADD CONSTRAINT FK_B077233DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id);');
            $this->addSql('ALTER TABLE vanillausergroup ADD CONSTRAINT FK_B077233DFE54D947 FOREIGN KEY (group_id) REFERENCES vanillaprogrammegroup (id);');
            $this->addSql('CREATE INDEX IDX_B077233DA76ED395 ON vanillausergroup (user_id);');
            $this->addSql('CREATE INDEX IDX_B077233DFE54D947 ON vanillausergroup (group_id);');
        }
    }

    /**
     * @param Schema $schema
     * @throws
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
