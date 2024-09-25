<?php

namespace Insead\MIMSeedFixturesBundle\DataFixtures\ORM;

use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Insead\MIMBundle\Entity\Role;

class LoadRolesData implements FixtureInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * @var string
     *
     */
    private static string $ROLES_DATA_FILE = 'src/Insead/MIMSeedFixturesBundle/DataFixtures/ORM/data/Roles.json';

    /**
     * {@inheritDoc}
     * @throws \Exception
     */

    public function load(ObjectManager $manager): void
    {
        $data_file =  $this->container->getParameter('kernel.project_dir') . '/../' . self::$ROLES_DATA_FILE;

        if( !file_exists($data_file) ) {
            throw new \Exception( 'Roles Data file does not exists: ' . self::$ROLES_DATA_FILE );
        } else {
            #read the contents of the json file
            $jsonString = file_get_contents($data_file);
            #convert json string into array object
            $jsonObj = json_decode($jsonString);
            foreach($jsonObj as $key => $value)
            {
                $role = new Role();
                $role->setName($value->name);
                $manager->persist($role);
                //$manager->flush();
            }

            $manager->flush();
        }
    }

    /**
     *  Order in which the Fixtures will be loaded
     */
    public function getOrder(): int
    {
        //first fixture to be loaded
        return 1;
    }

}
