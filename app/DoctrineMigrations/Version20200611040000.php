<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200611040000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        if($schema->hasTable('barco_usergroup')) {
            $this->addSql("INSERT INTO barco_usergroup (group_id, group_campus, group_name, group_date, group_term, group_class_nbr) VALUES
                            ('dzFdCbAKDfSoPnkda','FBL','ADMIN','2020-06-12 00:00:00',1900,1055),
                            ('TxY3uTwkfgua63jhT','FBL','Assitent','2020-06-12 00:00:00',1900,1055),
                            ('GEzE6kTe7E4nuiLRy','FBL','Bank of China Client Demo','2019-06-12 00:00:00',1900,1055),
                            ('PGWPce8k7KrqAXDSj','FBL','Coordinators','2020-06-12 00:00:00',1900,1055),
                            ('sYu5sCcvmzFx6Mnyp','FBL','Decision-Making in Difficult Times, 3-4 June, 2020 - C1','2020-06-12 00:00:00',1900,1055),
                            ('DB2pAisXfeWrAo5kW','FBL','Decision-Making in Difficult Times, 3-4 June, 2020 - C2','2020-06-12 00:00:00',1900,1055),
                            ('pfeqRs5Z4HSzuYkCw','FBL','Designing and Leading Collaboration - May 2020','2020-06-12 00:00:00',1900,1055),
                            ('zTCHCMahxgQbE6wxS','FBL','Designing and Leading Collaboration, 26-28 May 2020','2020-06-12 00:00:00',1900,1055),
                            ('JKZvxYwngJ3LmbGm5','FBL','Faculty','2020-06-12 00:00:00',1900,1055),
                            ('bzrHRo9WmN8yPisXu','FBL','HCILP Test','2019-06-12 00:00:00',1900,1055),
                            ('GZubNKHjC8XZgRWxh','FBL','IT Audit','2020-06-12 00:00:00',1900,1055),
                            ('9RbjMbvARLYXpxoiu','FBL','Merck University - March 2020','2020-06-12 00:00:00',1900,1055),
                            ('Ewe38hSuHsGR42Enm','FBL','Producers','2020-06-12 00:00:00',1900,1055),
                            ('mNwXjMNtS2uv6mxtX','FBL','Teachers','2020-06-12 00:00:00',1900,1055)
                          ");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
