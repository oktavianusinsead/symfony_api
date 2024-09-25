<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190421010254 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if($schema->hasTable('vanillaprogrammegroup')) {
            if(!$schema->getTable('vanillaprogrammegroup')->hasColumn('course_id')){
                $this->addSql("ALTER TABLE vanillaprogrammegroup ADD course_id INT DEFAULT NULL;");
            }

            $this->addSql("ALTER TABLE vanillaprogrammegroup ADD CONSTRAINT FK_4EEBADAB591CC992 FOREIGN KEY (course_id) REFERENCES courses (id);");
            $this->addSql("CREATE UNIQUE INDEX UNIQ_4EEBADAB591CC992 ON vanillaprogrammegroup (course_id);");
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
