<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160115153650 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->getTable('activities')->hasColumn('published')) {
            $this->addSql('ALTER TABLE activities ADD published TINYINT(1) DEFAULT NULL');
        }

    }

    public function postUp(Schema $schema):void
    {
        try {
            $this->write('Migrating data... Adding data for published column in activities table...');
            $this->updateActivities();
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'ERROR');
        }
    }

    public function updateActivities()
    {
        $activities = $this->request('SELECT * FROM activities');

        foreach ($activities as $activity) {
            $this->updateActivity($activity);
        }
    }

    public function updateActivity($activity)
    {
        $this->log('Migrating Activity.. ' . $activity['id'], 'INFO');
         $this->run(
            sprintf(
                'UPDATE activities SET published = TRUE WHERE id = %s',
                $activity['id']
            )
        );
    }

    public function request($sql)
    {
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $stmt    = $manager->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function run($sql)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $stmt    = $manager->getConnection()->prepare($sql);
        $stmt->execute();
    }

    public function log($log, $type = ' ')
    {
        echo( '[' . $type . '] ' . $log . PHP_EOL );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
