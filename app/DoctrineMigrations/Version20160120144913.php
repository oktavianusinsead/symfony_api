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
class Version20160120144913 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        if(!$schema->getTable('groups_sessions')->hasColumn('handouts_published')) {
            $this->addSql('ALTER TABLE groups_sessions ADD handouts_published TINYINT(1) DEFAULT NULL');
        }

    }

    public function postUp(Schema $schema):void
    {
        try {
            $this->write('Migrating data... Adding data for handouts_published column in groups_sessions table...');
            $this->updateScheduledSessions();
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'ERROR');
        }
    }

    public function updateScheduledSessions()
    {
        $groupSessions = $this->request('SELECT * FROM groups_sessions');

        foreach ($groupSessions as $groupSession) {
            $this->updateScheduledSession($groupSession);
        }
    }

    public function updateScheduledSession($groupSession)
    {
        $this->log('Migrating Scheduled Sessions.. ' . $groupSession['id'], 'INFO');
         $this->run(
            sprintf(
                'UPDATE groups_sessions SET handouts_published = TRUE WHERE id = %s',
                $groupSession['id']
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
