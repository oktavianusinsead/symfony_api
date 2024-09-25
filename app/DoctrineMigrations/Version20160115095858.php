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
class Version20160115095858 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs

    }

    public function postUp(Schema $schema):void
    {
        try {
            $this->write('Migrating data... Adding data for faculty_blogs and insead_knowledge columns in Programmes...');
            $this->updateProgrammes();
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'ERROR');
        }
    }

    public function updateProgrammes()
    {
        $programmes = $this->request('SELECT * FROM programmes');

        foreach ($programmes as $programme) {
            $this->updateProgramme($programme);
        }
    }

    public function updateProgramme($programme)
    {
        $this->log('Migrating Programme.. ' . $programme['id'], 'INFO');
         $this->run(
            sprintf(
                'UPDATE programmes SET faculty_blogs = FALSE, insead_knowledge = FALSE WHERE id = %s',
                $programme['id']
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
