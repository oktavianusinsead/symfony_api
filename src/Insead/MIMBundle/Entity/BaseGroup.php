<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseGroup
 */
#[ORM\MappedSuperclass]
class BaseGroup extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->original_start_date = $this->start_date;
        $this->original_end_date = $this->end_date;
    }

    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Group::class, inversedBy: 'baseGroup', fetch: 'LAZY')]
    protected $group;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'start_date', type: 'datetime')]
    #[Assert\NotBlank(message: 'Start Date cannot be blank.')]
    protected $start_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'end_date', type: 'datetime')]
    #[Assert\NotBlank(message: 'End Date cannot be blank.')]
    protected $end_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'original_start_date', type: 'datetime')]
    protected $original_start_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'original_end_date', type: 'datetime')]
    protected $original_end_date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'location', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Location cannot be blank.')]
    protected $location;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean')]
    protected $published = false;


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
     * Set id
     *
     * @param integer $id
     * @return BaseGroup
     */
    public function setId($id=null)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set Group
     *
     * @param Group $group
     * @return BaseGroup
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get Group
     *
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("group_id")
     *
     * @return string
     */
    public function getGroupId()
    {
        return $this->getGroup()->getId();
    }

    /**
     * Set start_date
     *
     * @param \DateTime $startDate
     * @return BaseGroup
     */
    public function setStartDate($startDate)
    {
        $this->start_date = $startDate;

        return $this;
    }

    /**
     * Get start_date
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Set end_date
     *
     * @param \DateTime $endDate
     * @return BaseGroup
     */
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;

        return $this;
    }

    /**
     * Get end_date
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Set location
     *
     * @param string $location
     * @return BaseGroup
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set published
     *
     * @param boolean $published
     * @return BaseGroup
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Get published
     *
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Set original_start_date
     *
     * @param \DateTime $startDate
     * @return BaseGroup
     */
    public function setOriginalStartDate($startDate)
    {
        $this->original_start_date = $startDate;

        return $this;
    }

    /**
     * Get original_start_date
     *
     * @return \DateTime
     */
    public function getOriginalStartDate()
    {
        return $this->original_start_date;
    }

    /**
     * Set original_end_date
     *
     * @param \DateTime $endDate
     * @return BaseGroup
     */
    public function setOriginalEndDate($endDate)
    {
        $this->original_end_date = $endDate;

        return $this;
    }

    /**
     * Get original_end_date
     *
     * @return \DateTime
     */
    public function getOriginalEndDate()
    {
        return $this->original_end_date;
    }

    /**
     * This function handles the cloning of Session|Activity schedule
     * This will not persist the data
     *
     */
    public function __clone()
    {
        $this->setId();

        $currentDateTime = new \DateTime();
        $this->setCreated($currentDateTime);
        $this->setUpdated($currentDateTime);
    }
}
