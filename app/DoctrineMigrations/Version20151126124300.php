<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Programme;

use \PDO;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151126124300 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {

        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE programmes (id INT AUTO_INCREMENT NOT NULL, 
                                                name VARCHAR(255) NOT NULL, 
                                                code VARCHAR(255) NOT NULL, 
                                                welcome LONGTEXT NOT NULL, 
                                                published TINYINT(1) NOT NULL, 
                                                link_webmail TINYINT(1) NOT NULL, 
                                                link_yammer TINYINT(1) NOT NULL, 
                                                link_myinsead TINYINT(1) NOT NULL, 
                                                created_at DATETIME NOT NULL, 
                                                updated_at DATETIME NOT NULL, 
                                                PRIMARY KEY(id))');

        $this->addSql('ALTER TABLE courses ADD programme_id INT DEFAULT NULL,
                        ADD start_date DATETIME NOT NULL,
                        ADD end_date DATETIME NOT NULL,
                        ADD country VARCHAR(255) NOT NULL,
                        ADD timezone VARCHAR(255) NOT NULL,
                        ADD dtype VARCHAR(255) NOT NULL,
                        ADD ps_session_code VARCHAR(255) NOT NULL,
                        ADD ps_class_descr LONGTEXT NOT NULL,
                        ADD ps_class_section VARCHAR(255) NOT NULL,
                        ADD ps_crse_id VARCHAR(255) NOT NULL, 
                        ADD ps_acad_career VARCHAR(255) NOT NULL, 
                        ADD ps_strm VARCHAR(255) NOT NULL, 
                        ADD ps_class_nbr VARCHAR(255) NOT NULL, 
                        ADD ps_campus VARCHAR(255) DEFAULT NULL, 
                        ADD ps_srr_component VARCHAR(255) DEFAULT NULL, 
                        ADD ps_class_stat VARCHAR(255) DEFAULT NULL, 
                        ADD ps_lms_url VARCHAR(255) DEFAULT NULL, 
                        ADD ps_location VARCHAR(255) DEFAULT NULL'
        );
        $this->addSql('ALTER TABLE programmes ADD faculty_blogs TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE programmes ADD insead_knowledge TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE courses ADD CONSTRAINT FOREIGN KEY (programme_id) REFERENCES programmes (id)');
        $this->addSql('ALTER TABLE courses_users_role ADD programme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE courses_users_role ADD CONSTRAINT FOREIGN KEY (programme_id) REFERENCES programmes (id)');

    }

    public function postUp(Schema $schema):void
    {
        try {
            $this->write('Migrating data...');
            $this->createProgrammes();
        } catch (\Exception $e) {
            $this->write($e->getMessage());
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }

    public function createProgrammes()
    {
        $courses = $this->request('SELECT * FROM courses');

        foreach ($courses as $course) {
            $this->createProgramme($course);
        }
    }

    public function createProgramme($course)
    {
        $this->log(sprintf('Creating Programme based on course %s (%s)', $course[ 'id' ], $course[ 'name' ]));

        $programme = new Programme();
        $programme->setName($course[ 'name' ]);
        $programme->setCode($course[ 'abbreviation' ]);
        $programme->setWelcome($course[ 'welcome' ]);
        if(array_key_exists('link_webmail', $course)) $programme->setLinkWebmail($course[ 'link_webmail' ]);
        if(array_key_exists('link_yammer', $course)) $programme->setLinkYammer($course[ 'link_yammer' ]);
        if(array_key_exists('link_myinsead', $course)) $programme->setLinkMyinsead($course[ 'link_myinsead' ]);
        $programme->setPublished($course[ 'published' ]);

        $this->save($programme);

        $this->run(sprintf('UPDATE courses SET programme_id = \'%s\' WHERE id = %s', $programme->getId(), $course[ 'id' ]));

        $course[ 'programme_id' ] = $programme->getId();

        $this->updateCourse($course);
        $this->updateCoursesUserRole($course, $programme);
    }

    public function updateCourse($course)
    {
        $this->log(sprintf('Updating dates on course %s (%s)', $course[ 'id' ], $course[ 'name' ]));

        $sql     = 'SELECT * FROM modules WHERE course_id = :course_id';

        $data = [
            [
                "field" => ":course_id",
                "value" => $course[ 'id' ],
                "type" => PDO::PARAM_INT
            ]
        ];

        $modules = $this->request($sql,$data);

        if (count($modules)) {

            $this->log('Found ' . count($modules) . ' related modules...', '@');

            $min      = '3000-01-01';
            $max      = '1000-01-01';
            $country  = NULL;
            $timeZone = NULL;

            foreach ($modules as $module) {
                $min      = $module[ 'start_date' ] < $min ? $module[ 'start_date' ] : $min;
                $max      = $module[ 'end_date' ] > $max ? $module[ 'end_date' ] : $max;
                $country  = !$country ? $module[ 'country' ] : $country;
                $timeZone = !$timeZone ? $module[ 'timezone' ] : $timeZone;
            }

            $this->log(
                'Updating course with Start Date: ' . $min .
                ' End Date: ' . $max .
                ' Country: ' . $country .
                ' TimeZone: ' . $timeZone,
                '@');

            $this->run(
                sprintf(
                    'UPDATE courses
                     SET start_date = \'%s\',
                     end_date = \'%s\',
                     country = \'%s\',
                     timezone = \'%s\'
                     WHERE id = %s',
                    $min, $max, $country, $timeZone, $course[ 'id' ]
                )
            );
        }
    }

    public function updateCoursesUserRole($course, Programme $programme)
    {
        $this->log(sprintf('Updating Course (%s) User Role with Programme (%s)', $course[ 'id' ], $programme->getId()),
            '@');

        $this->run(
            sprintf(
                'UPDATE courses_users_role SET programme_id = %s WHERE course_id = %s',
                $programme->getId(), $course[ 'id' ]
            )
        );
    }

    public function run($sql)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $stmt    = $manager->getConnection()->prepare($sql);
        $stmt->execute();
    }

    public function request($sql,$data)
    {
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $stmt    = $manager->getConnection()->prepare($sql);

        foreach( $data as $item ) {
            $stmt->bindParam( $item["field"], $item["value"], $item["type"] );
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function save($entity)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $manager->persist($entity);
        $manager->flush();
    }

    public function get($className, $id)
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');

        return $manager->find($className, $id);
    }

    public function transactionRollback()
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $manager->rollback();
    }

    public function transactionStart()
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');
        $manager->beginTransaction();
    }

    public function log($log, $type = ' ')
    {
        echo( '[' . $type . '] ' . $log . PHP_EOL );
    }

}
