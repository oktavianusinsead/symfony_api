<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

/**
 * Activity
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: 'activities')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Activity extends Base
{
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
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'activities')]
    private $course;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text')]
    private $description = '';

    /**
     * @var integer
     */
    #[ORM\Column(name: 'position', type: 'integer', nullable: true)]
    private $position = 0;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean', nullable: true)]
    private $published = FALSE;

    /**
     * @var \DateTime
     *
     * @Serializer\SerializedName("slot_start")
     */
    #[ORM\Column(name: 'slot_start', type: 'datetime')]
    private $start_date;

    /**
     * @var \DateTime
     *
     * @Serializer\SerializedName("slot_end")
     */
    #[ORM\Column(name: 'slot_end', type: 'datetime')]
    private $end_date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Please choose an Activity Type')]
    private $type;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_scheduled', type: 'boolean', options: ['default' => 0])]
    private $is_scheduled = FALSE;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \GroupActivity::class, mappedBy: 'activity', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $group_activities;

    /**
     * @Serializer\Exclude
     **/
    private $hideGroupActivities = FALSE;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults();
    }

    private function setDefaults() {
        $this->group_activities = new ArrayCollection();
    }


    public function doNotShowGroupActivities($hide)
    {
        $this->hideGroupActivities = $hide;
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
     * Set id
     *
     * @param integer $id
     * @return Activity
     */
    public function setId($id=null)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set course
     *
     * @param Course $course
     * @return Activity
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("course_id")
     *
     * @return string
     */
    public function getCourseId()
    {
        return $this->getCourse()->getId();
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Activity
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Activity
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param integer $position
     * @return Activity
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Set published
     *
     * @param boolean $published
     * @return Activity
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
     * Set start_date
     *
     * @param \DateTime $start_date
     * @return Activity
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;

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
     * @param \DateTime $end_date
     * @return Activity
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;

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
     * Set type
     *
     * @param string $type
     * @return Activity
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set is_scheduled
     *
     * @param string
     * @return Activity
     */
    public function setActivityScheduled($is_scheduled)
    {
        $this->is_scheduled = $is_scheduled;

        return $this;
    }

    /**
     * Get is_scheduled
     *
     * @return string
     */
    public function getActivityScheduled()
    {
        return $this->is_scheduled;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("group_activities")
     *
     * @return array()
     */
    public function getGroupActivityIds()
    {
        $groupActivities = [];

        if($this->hideGroupActivities) {
            return $groupActivities;
        }
        if($this->serializePublishedSubEntities) {
            if($this->group_activities) {
                if($this->group_activities->toArray() > 0) {
                    $group_activities = $this->group_activities->toArray();

                    /** @var GroupActivity $group_activity */
                    foreach($group_activities as $group_activity) {
                        array_push($groupActivities, $group_activity->getId());
                    }
                }
            }
        }
        return $groupActivities;
    }

    public function getGroupActivities()
    {
        $group_activities = [];

        if( $this->group_activities::class == \Doctrine\ORM\PersistentCollection::class
            || $this->group_activities::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $group_activities = $this->group_activities;
        }

        return $group_activities;
    }

    /**
     * This function handles the cloning of Activity
     * This will not persist the data
     *
     */
    public function __clone()
    {
        $this->setId();
        $this->setDefaults();

        $currentDateTime = new \DateTime();
        $this->setCreated($currentDateTime);
        $this->setUpdated($currentDateTime);
    }
}
