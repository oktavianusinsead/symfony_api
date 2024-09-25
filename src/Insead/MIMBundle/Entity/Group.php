<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Group
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: '`groups`')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Group extends Base
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
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'groups', fetch: 'LAZY')]
    private $course;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinTable(name: 'groups_users')]
    #[ORM\ManyToMany(targetEntity: \User::class, inversedBy: 'userGroups', cascade: ['persist'])]
    protected $group_members;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \GroupSession::class, mappedBy: 'group', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $group_sessions;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \GroupActivity::class, mappedBy: 'group', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $group_activities;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults();
    }

    private function setDefaults() {
        $this->group_members    = new ArrayCollection();
        $this->group_sessions   = new ArrayCollection();
        $this->group_activities = new ArrayCollection();
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    private $name;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'start_date', type: 'datetime')]
    #[Assert\NotBlank(message: 'Start Date cannot be blank.')]
    private $start_date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'end_date', type: 'datetime')]
    #[Assert\NotBlank(message: 'End Date cannot be blank.')]
    private $end_date;

    /**
     * @var string
     */
    #[ORM\Column(name: 'colour', type: 'integer')]
    #[Assert\NotBlank(message: 'Colour should be selected.')]
    private $colour;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ps_stdnt_group', type: 'string', length: 255, nullable: true)]
    private $ps_stdnt_group;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ps_descr', type: 'string', length: 255, nullable: true)]
    private $ps_descr;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'course_default', type: 'boolean')]
    protected $course_default = false;


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
     * @return Group
     */
    public function setId($id=null)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get list of all Group Sessions
     *
     * @return array()
     */
    public function getGroupSessions()
    {
        $groupSessions = [];

        if ($this->group_sessions) {
            foreach($this->group_sessions as $group_session) {
                array_push($groupSessions, $group_session);
            }
        }

        return $groupSessions;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("group_sessions")
     *
     * @return string
     */
    public function getGroupSessionsObjects()
    {
        $groupSessions = [];

        foreach($this->getGroupSessions() as $groupSession) {
            if($this->serializePublishedSubEntities) {
                if($groupSession->getPublished() && $groupSession->getSession()->getPublished()) {
                    if($this->serializeFully) {
                        array_push($groupSessions, $groupSession);
                    } else {
                        array_push($groupSessions, $groupSession->getId());
                    }
                }
            } else {
                if($this->serializeFully) {
                    array_push($groupSessions, $groupSession);
                } else {
                    array_push($groupSessions, $groupSession->getId());
                }
            }
        }

        return $groupSessions;
    }

    /**
     * Get list of all Group Activities
     *
     * @return array()
     */
    public function getGroupActivities()
    {
        $groupActivities = [];
        if ($this->group_activities) {
            foreach($this->group_activities as $group_activity) {
                array_push($groupActivities, $group_activity);
            }
        }

        return $groupActivities;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("group_activities")
     *
     * @return string
     */
    public function getGroupActivitiesObjects()
    {
        $groupActivities = [];

        foreach($this->getGroupActivities() as $groupActivity) {
            if($this->serializePublishedSubEntities) {
                if($groupActivity->getPublished() && $groupActivity->getActivity()->getPublished()) {
                    if($this->serializeFully) {
                        array_push($groupActivities, $groupActivity);
                    } else {
                        array_push($groupActivities, $groupActivity->getId());
                    }
                }
            } else {
                if($this->serializeFully) {
                    array_push($groupActivities, $groupActivity);
                } else {
                    array_push($groupActivities, $groupActivity->getId());
                }
            }
        }

        return $groupActivities;
    }

    /**
     * Set course
     *
     * @param Course $course
     * @return Group
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
     * Add a user to this group
     *
     * @return Group
     */
    public function addUser(User $user)
    {
        if(!$this->group_members->contains($user)) {
            $this->group_members->add($user);
        }
        return $this;
    }

    /**
     * Remove a user from this group
     *
     * @return Group
     */
    public function removeUser(User $user)
    {
        if($this->group_members->contains($user)) {
            $this->group_members->removeElement($user);
        }
        return $this;
    }

    /**
     * Get list of Users assigned to this Group
     *
     * @return array()
     */
    public function getUsers()
    {
        return $this->group_members->toArray();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("students")
     *
     * @return array
     */
    public function getUsersList()
    {
        $students = [];

        //hide information peoplesoft ids if the user is accessing the programme with readonly access
        if (!$this->getCourse()->getProgramme()->checkIfMy() && !$this->getCourse()->getProgramme()->checkIfSuperAdmin()) {
            return [];
        }

        foreach($this->getUsers() as $user) {
            array_push($students, $user->getPeoplesoftId());
        }
        return $students;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set start_date
     *
     * @param \DateTime $startDate
     * @return Group
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
     * @return Group
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
     * @return integer
     */
    public function getColour()
    {
        return $this->colour;
    }

    /**
     * @param integer $colour
     *
     * @return Group
     */
    public function setColour($colour)
    {
        $this->colour = $colour;

        return $this;
    }

    /**
     * Set ps_stdnt_group
     *
     * @param string $psStdntGroup
     * @return Group
     */
    public function setPsStdntGroup($psStdntGroup)
    {
        $this->ps_stdnt_group = $psStdntGroup;

        return $this;
    }

    /**
     * Get ps_stdnt_group
     *
     * @return string
     */
    public function getPsStdntGroup()
    {
        return $this->ps_stdnt_group;
    }

    /**
     * Set ps_descr
     *
     * @param string $psDescr
     * @return Group
     */
    public function setPsDescr($psDescr)
    {
        $this->ps_descr = $psDescr;

        return $this;
    }

    /**
     * Get ps_descr
     *
     * @return string
     */
    public function getPsDescr()
    {
        return $this->ps_descr;
    }

    /**
     * Set course_default
     *
     * @param boolean $course_default
     * @return Group
     */
    public function setCourseDefault($course_default)
    {
        $this->course_default = $course_default;

        return $this;
    }

    /**
     * Get course_default
     *
     * @return boolean
     */
    public function getCourseDefault()
    {
        return $this->course_default;
    }

    /**
     * This function handles the cloning of Groups
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
        $this->setPsStdntGroup(null);
        $this->setPsDescr(null);
    }
}
