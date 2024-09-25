<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Insead\MIMBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Fix for new role (inseadteam)
 */
class Version20190622134616 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
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
            'inseadteam'
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
}
