<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Insead\MIMBundle\Entity\Group;
use Insead\MIMBundle\Entity\Course;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151216152025 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        if(!$schema->getTable('courses')->hasColumn('start_date')) {
            $this->addSql('ALTER TABLE courses ADD start_date DATETIME DEFAULT NULL');
        }

        if(!$schema->getTable('courses')->hasColumn('end_date')) {
            $this->addSql('ALTER TABLE courses ADD end_date DATETIME DEFAULT NULL');
        }
    }

    public function postUp(Schema $schema):void
    {
        try {
            $this->write('Migrating Courses with default Group if it does not have one already...');
            $this->createDefaultGroups();
        } catch (\Exception $e) {
            $this->write($e->getMessage());
        }
    }

    public function createDefaultGroups()
    {
        $courses = $this->request('SELECT * FROM courses');

        foreach ($courses as $course) {
            if(!$this->checkIfDefaultExists($course)) {
                $this->write('No default group for course.. ' . $course[ 'id' ]);

                // Create Default Group and add people
                $manager = $this->container->get('doctrine.orm.entity_manager');
                $courseObj = $manager->getRepository('MIMBundle:Course')
                                     ->find($course[ 'id' ]);
                if (count($courseObj)) {
                    $this->createDefaultGroup($courseObj);
                }
            }
        }
    }

    public function checkIfDefaultExists($course)
    {
        $exists = FALSE;
        $defaultGroups = $this->request('SELECT * FROM groups WHERE course_id=:course_id AND course_default=1', $course[ 'id' ]);
        if (count($defaultGroups)) {
            $exists = TRUE;
        }
        return $exists;
    }

    public function createDefaultGroup($course)
    {

        $group = new Group();

        $group->setCourse($course);
        $group->setName('Everyone' );
        $group->setStartDate($course->getStartDate());
        $group->setEndDate($course->getEndDate());
        $group->setColour( -1 );
        $group->setPsStdntGroup( '...' );
        $group->setPsDescr( '...' );
        $group->setCourseDefault(TRUE);
        $this->save($group);

        // Add people to this Group
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $courseSubscriptions = $manager->getRepository('MIMBundle:CourseSubscription')->findBy(['course' => $course->getId()]);
        if (sizeof($courseSubscriptions) > 0) {
            foreach($courseSubscriptions as $subscription) {
                $group = $group->addUser($subscription->getUser());
                $this->save($group);
            }

        }
    }

    public function request($sql , $where=null)
    {
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $stmt    = $manager->getConnection()->prepare($sql);
        if($where===null)
        $stmt->execute();
        else
        $stmt->execute(["course_id"=>$where]);

        return $stmt->fetchAll();
    }

    public function run($sql)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $stmt    = $manager->getConnection()->prepare($sql);
        $stmt->execute();
    }

    public function save($entity)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $manager->persist($entity);
        $manager->flush();
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
