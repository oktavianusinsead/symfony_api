<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191022094248 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if(!$schema->hasTable('user_profiles')) {
            $this->addSql("CREATE TABLE user_profiles (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, bio VARCHAR(255) DEFAULT NULL, job_title VARCHAR(255) DEFAULT NULL, preferred_job_title VARCHAR(255) DEFAULT NULL, organization_id VARCHAR(255) DEFAULT NULL, organization_title VARCHAR(255) DEFAULT NULL, upn_email VARCHAR(255) DEFAULT NULL, nationality VARCHAR(255) DEFAULT NULL, constituent_types VARCHAR(255) DEFAULT NULL, cell_phone_prefix VARCHAR(255) DEFAULT NULL, cell_phone VARCHAR(255) DEFAULT NULL, personal_phone_prefix VARCHAR(255) DEFAULT NULL, personal_phone VARCHAR(255) DEFAULT NULL, work_phone_prefix VARCHAR(255) DEFAULT NULL, work_phone VARCHAR(255) DEFAULT NULL, preferred_phone INT DEFAULT NULL, personal_email VARCHAR(255) DEFAULT NULL, work_email VARCHAR(255) DEFAULT NULL, preferred_email INT DEFAULT NULL, address_line_1 VARCHAR(255) DEFAULT NULL, address_line_2 VARCHAR(255) DEFAULT NULL, address_line_3 VARCHAR(255) DEFAULT NULL, state VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, has_access TINYINT(1) DEFAULT '1', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_6BBD6130A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB;");
            $this->addSql("ALTER TABLE user_profiles ADD CONSTRAINT FK_6BBD6130A76ED395 FOREIGN KEY (user_id) REFERENCES users (id);");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
