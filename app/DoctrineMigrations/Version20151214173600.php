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
class Version20151214173600 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE groups CHANGE colour colour INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sessions ADD position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activities ADD position INT DEFAULT NULL');
    }

    public function postUp(Schema $schema):void
    {
        try {
            $this->write('Migrating Sessions with position data...');
            $this->updateSessions();
            $this->updateActivities();
        } catch (\Exception $e) {
            $this->write($e->getMessage());
        }
    }

    public function updateSessions()
    {
        $sessions = $this->request('SELECT * FROM sessions');

        foreach ($sessions as $session) {
            $this->addDefaultSessionPosition($session);
        }
    }

    public function addDefaultSessionPosition($session)
    {
        $defaultPosition = 0;

        $this->run(
                sprintf(
                    'UPDATE sessions
                     SET position = %s  
                     WHERE id = %s',
                    $defaultPosition, $session[ 'id' ]
                )
            );
    }

    public function updateActivities()
    {
        $activities = $this->request('SELECT * FROM activities');

        foreach ($activities as $activity) {
            $this->addDefaultActivityPosition($activity);
        }
    }

    public function addDefaultActivityPosition($activity)
    {
        $defaultPosition = 0;

        $this->run(
                sprintf(
                    'UPDATE activities
                     SET position = %s  
                     WHERE id = %s',
                    $defaultPosition, $activity[ 'id' ]
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

        $this->addSql('ALTER TABLE groups CHANGE colour colour INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sessions DROP position');
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
