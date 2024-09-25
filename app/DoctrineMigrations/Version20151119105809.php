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
 * This migration will add a new "contact" role,
 * but will also check for the rest of the roles and
 * will add them if missing.
 */
class Version20151119105809 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        if(!$schema->hasTable('roles')) {
            $this->addSql('CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, 
                            name VARCHAR(255) NOT NULL, 
                            created_at DATETIME NOT NULL, 
                            updated_at DATETIME NOT NULL, 
                            PRIMARY KEY(id))');

        }
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
