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
class Version20151214165314 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups ADD colour INT DEFAULT NULL');
    }

    public function postUp(Schema $schema):void
    {
        try {
            $this->write('Migrating Groups data...');
            $this->updateGroups();
        } catch (\Exception $e) {
            $this->write($e->getMessage());
        }
    }

    public function updateGroups()
    {
        $groups = $this->request('SELECT * FROM groups');

        foreach ($groups as $group) {
            $this->addDefaultGroupColour($group);
        }
    }

    public function addDefaultGroupColour($group)
    {
        $defaultColour = 1;
        $default_ps_student_group = '...';
        $default_ps_description = '...';
        $this->run(
                sprintf(
                    'UPDATE groups
                     SET colour = %s,
                     ps_stdnt_group = \'%s\',
                     ps_descr = \'%s\' 
                     WHERE id = %s',
                    $defaultColour, $default_ps_student_group, $default_ps_description, $group[ 'id' ]
                )
            );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups DROP colour');
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

}
