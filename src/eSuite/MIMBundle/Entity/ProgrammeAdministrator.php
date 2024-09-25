<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * ProgrammeAdministrator
 */
#[ORM\Table(name: 'programme_administrator')]
#[ORM\UniqueConstraint(name: 'programme_administrator_unique', columns: ['programme_id', 'user_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ProgrammeAdministrator extends Base
{
    /**
     * @var integer
     *
     * @Serializer\Exclude
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var boolean
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'is_owner', type: 'boolean')]
    private $owner = FALSE;


    #[ORM\JoinColumn(name: 'programme_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Programme::class, inversedBy: 'programmeAdmins')]
    private $programme;

    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'programmeAdmins')]
    private $user;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set programme
     *
     * @param Programme $programme
     * @return ProgrammeAdministrator
     */
    public function setProgramme($programme)
    {
        $this->programme = $programme;

        return $this;
    }

    /**
     * Get programme
     *
     * @return Programme
     */
    public function getProgramme()
    {
        return $this->programme;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return ProgrammeAdministrator
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set Owner
     *
     * @param boolean $owner
     * @return ProgrammeAdministrator
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get Owner
     *
     * @return boolean
     */
    public function getOwner()
    {
        return $this->owner;
    }
}
