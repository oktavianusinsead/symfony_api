<?php
/**
 * Created by PhpStorm.
 * User: jeffersonmartin
 * Date: 1/28/19
 * Time: 3:41 PM
 */

namespace Insead\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

/**
 * VanillaProgrammeGroup
 */
#[ORM\Table(name: 'vanillaprogrammegroup')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class VanillaProgrammeGroup extends Base
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'vanilla_group_id', type: 'string', length: 255, nullable: true)]
    protected $vanillaGroupId;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'programme_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Programme::class, inversedBy: 'programmegroups')]
    protected $programme;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'coursegroup')]
    protected $course;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    protected $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'groupDescription', type: 'string', length: 255, nullable: true)]
    protected $description;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \VanillaUserGroup::class, mappedBy: 'group', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $vanillaUserGroup;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isInitial', type: 'boolean', options: ['default' => 0], nullable: false)]
    protected $initial;

    public function __construct()
    {
        parent::__construct();
        $this->vanillaUserGroup = new ArrayCollection();
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
     * Set programme
     *
     * @param Programme $programme
     * @return VanillaProgrammeGroup
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
     * Set vanilla group id
     *
     * @param String $vanillaGroupId
     * @return VanillaProgrammeGroup
     */
    public function setVanillaGroupId($vanillaGroupId)
    {
        $this->vanillaGroupId = $vanillaGroupId;

        return $this;
    }

    /**
     * Get vanilla group id
     *
     * @return vanillagroupid
     */
    public function getVanillaGroupId()
    {
        return $this->vanillaGroupId;
    }

    /**
     * Set vanilla group name
     *
     * @param String $name
     * @return VanillaProgrammeGroup
     */
    public function setVanillaGroupName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get vanilla group name
     *
     * @return string
     */
    public function getVanillaGroupName()
    {
        return $this->name;
    }

    /**
     * Set vanilla group description
     *
     * @param String $description
     * @return VanillaProgrammeGroup
     */
    public function setVanillaGroupDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get vanilla group description
     *
     * @return string
     */
    public function getVanillaGroupDescription()
    {
        return $this->description;
    }


    /**
     * Get vanilla user group
     *
     * @return VanillaUserGroup
     */
    public function getvanillaUserGroup()
    {
        return $this->vanillaUserGroup;
    }

    /**
     * @param boolean $initial
     * @return VanillaProgrammeGroup
     */
    public function setInitial($initial){
        $this->initial = $initial;

        return $this;
    }
    /**
     * @return bool
     */
    public function getInitial(): bool
    {
        return $this->initial;
    }

    /**
     * @return VanillaProgrammeGroup
     */
    public function setCourse(mixed $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCourse()
    {
        if ($this->course)
            return $this->course;
        else
            return [];
    }
}
