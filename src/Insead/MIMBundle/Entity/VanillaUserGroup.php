<?php
/**
 * Created by PhpStorm.
 * User: jeffersonmartin
 * Date: 2019-02-13
 * Time: 14:06
 */

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

/**
 * VanillaUserGroup
 */
#[ORM\Table(name: 'vanillausergroup')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class VanillaUserGroup extends Base
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'vanillaUserGroup')]
    private $user;

    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \VanillaProgrammeGroup::class, inversedBy: 'vanillaUserGroup')]
    private $group;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isAdded', type: 'boolean', options: ['default' => 0], nullable: false)]
    protected $added;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isRemove', type: 'boolean', options: ['default' => 0], nullable: false)]
    protected $remove;

    public function __construct()
    {
        parent::__construct();
    }

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
     * Set User object
     *
     * @param User $user
     * @return VanillaUserGroup
     */
    public function setUser($user){
        $this->user = $user;

        return $this;
    }

    /**
     * Retrieve user object
     *
     * @return User
     */
    public function getUser(){
        return $this->user;
    }

    /**
     * @param VanillaProgrammeGroup $group
     * @return VanillaUserGroup
     */
    public function setGroup($group){
        $this->group = $group;

        return $this;
    }

    /**
     * Retrieve Vanilla Group
     *
     * @return VanillaProgrammeGroup
     */
    public function getGroup(){
        return $this->group;
    }

    /**
     * Set the flag to know if user has been added into the group false = no, true = added
     * @param boolean $added
     * @return $this
     */
    public function setAdded($added){
        $this->added = $added;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAdded(): bool {
        return $this->added;
    }

    /**
     * @param boolean $remove
     * @return VanillaUserGroup
     */
    public function setRemove($remove){
        $this->remove = $remove;

        return $this;
    }
    /**
     * @return bool
     */
    public function getRemove(): bool
    {
        return $this->remove;
    }

}
