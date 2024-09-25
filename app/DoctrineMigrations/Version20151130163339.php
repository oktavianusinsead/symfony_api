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
class Version20151130163339 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activities ADD course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sessions ADD course_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');
        $this->addSql('ALTER TABLE sessions ADD CONSTRAINT FOREIGN KEY (course_id) REFERENCES courses (id)');
    }

    public function postUp(Schema $schema):void
    {
        try {
            $this->write('Migrating data... Adding course_id for sessions and activities...');
            $this->migrateSessions();
            $this->migrateActivities();
        } catch (\Exception $e) {
            $this->write($e->getMessage());
        }
    }

    public function migrateSessions()
    {
        $sessions = $this->request('SELECT * FROM sessions');

        foreach ($sessions as $session) {
            $this->addCourseToSession($session);
        }
    }

    public function addCourseToSession($session)
    {
        $module_id = $session[ 'module_id' ];

        $sql = 'SELECT * FROM modules WHERE id = :where_cond '  ;
        $modules = $this->request($sql,$module_id);

        if (count($modules)) {
            $module = $modules[0];
            $this->write('Module Found ! Now getting its Course...');
            $sql = 'SELECT * FROM courses WHERE id = :where_cond' ;
            $courses = $this->request($sql,$module[ 'course_id']);

            if (count($courses)) {
                $course = $courses[0];
                $this->write('Course Found ! Now Setting course id for Session...');
                $this->run(
                sprintf(
                    'UPDATE sessions
                     SET course_id = %s 
                     WHERE id = %s',
                     $course[ 'id' ], $session[ 'id' ]
                )
            );
            }
        }
    }

    public function migrateActivities()
    {
        $activities = $this->request('SELECT * FROM activities');

        foreach ($activities as $activity) {
            $this->addCourseToActivity($activity);
        }
    }

    public function addCourseToActivity($activity)
    {
        $module_id = $activity[ 'module_id' ];

        $sql = 'SELECT * FROM modules WHERE id = :where_cond';
        $modules = $this->request($sql ,$module_id);

        if (count($modules)) {
            $module = $modules[0];
            $this->write('Module Found ! Now getting its Course...');
            $sql = 'SELECT * FROM courses WHERE id = :where_cond';
            $courses = $this->request($sql, $module[ 'course_id' ]);

            if (count($courses)) {
                $course = $courses[0];
                $this->write('Course Found ! Now Setting course id for Activity...');
                $this->run(
                sprintf(
                    'UPDATE activities
                     SET course_id = %s 
                     WHERE id = %s',
                     $course[ 'id' ], $activity[ 'id' ]
                )
            );
            }
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


    public function request($sql, $where_cond =null)
    {
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $stmt    = $manager->getConnection()->prepare($sql);
        if($where_cond===null)
        $stmt->execute();
        else
        $stmt->execute(["where_cond"=>$where_cond]);

        return $stmt->fetchAll();
    }

    public function run($sql)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $stmt    = $manager->getConnection()->prepare($sql);
        $stmt->execute();
    }

    public function get($className, $id)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');

        return $manager->find($className, $id);
    }

    public function save($entity)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $manager->persist($entity);
        $manager->flush();
    }

    public function transactionStart()
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $manager->beginTransaction();
    }

    public function transactionRollback()
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $manager->rollback();
    }

    public function log($log, $type = ' ')
    {
        echo( '[' . $type . '] ' . $log . PHP_EOL );
    }
}
