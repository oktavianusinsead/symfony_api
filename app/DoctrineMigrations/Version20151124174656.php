<?php

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Insead\MIMBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Fix to roles
 */
class Version20151124174656 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {

    }

    public function postUp(Schema $schema):void
    {
        /** @var EntityManager $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');

        $roles = [
            'admin',
            'coordinator',
            'director',
            'faculty',
            'student',
            'alumni',
            'contact',
            'consultant',
            'guest',
            'hidden',
            'manager',
            'advisor',
        ];

        foreach($roles as $role)
        {
            $builder = $manager->createQueryBuilder();

            $result = $builder->select('r')
                              ->from(\Insead\MIMBundle\Entity\Role::class,'r')
                              ->where('r.name = :name')
                              ->setParameter('name', $role)
                              ->getQuery()
                              ->getArrayResult();

            if (empty($result)) {
                $rolePersistence = new Role();
                $rolePersistence->setName($role);
                $manager->persist($rolePersistence);
            }

        }

        $manager->flush();
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
