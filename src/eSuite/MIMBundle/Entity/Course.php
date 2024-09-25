<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use esuite\MIMBundle\Annotations\Validator as FormAssert;

/**
 * Course
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: 'courses')]
#[ORM\Index(name: 'id_idx', columns: ['id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Course extends Base
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
    #[ORM\JoinColumn(name: 'programme_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Programme::class, inversedBy: 'courses')]
    protected $programme;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \CourseSubscription::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $courseSubscriptions;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \AdminSessionLocation::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $adminSessionLocation;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \LearningJourney::class, mappedBy: 'course_id', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $learning_journey;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults();
    }

    private function setDefaults() {
        $this->courseSubscriptions      = new ArrayCollection();
        $this->sessions                 = new ArrayCollection();
        $this->activities               = new ArrayCollection();
        $this->announcements            = new ArrayCollection();
        $this->tasks                    = new ArrayCollection();
        $this->groups                   = new ArrayCollection();
        $this->adminSessionLocation     = new ArrayCollection();
        $this->courseBackupEmail        = new ArrayCollection();
        $this->userDocuments            = new ArrayCollection();
        $this->userSubtasks             = new ArrayCollection();
        $this->userFavourites           = new ArrayCollection();
        $this->userAnnouncements        = new ArrayCollection();
        $this->coursegroup              = new ArrayCollection();
        $this->original_country         = $this->country;
        $this->original_timezone        = $this->timezone;
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'uid', type: 'string', length: 255, unique: true)]
    protected $uid;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    protected $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'abbreviation', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Programme code cannot be blank.')]
    protected $abbreviation;

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
     * @var string
     */
    #[ORM\Column(name: 'country', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Country should be selected.')]
    protected $country;

    /**
     * @var string
     */
    #[ORM\Column(name: 'timezone', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Timezone should be selected.')]
    protected $timezone;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean')]
    protected $published = false;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Session::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $sessions;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Activity::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $activities;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Task::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $tasks;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Announcement::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $announcements;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Group::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $groups;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToOne(targetEntity: \CourseBackup::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $courseBackup;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \CourseBackupEmail::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $courseBackupEmail;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\Column(name: 'box_group_id', type: 'string', length: 255)]
    protected $box_group_id;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserDocument::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserSubtask::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userSubtasks;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserFavourite::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userFavourites;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserAnnouncement::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userAnnouncements;

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_crse_id")
     */
    #[ORM\Column(name: 'ps_crse_id', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Peoplesoft Course Id cannot be blank.')]
    private $ps_crse_id = '';

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_acad_career")
     */
    #[ORM\Column(name: 'ps_acad_career', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Peoplesoft Academic career cannot be blank.')]
    private $ps_acad_career = '';

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_strm")
     */
    #[ORM\Column(name: 'ps_strm', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Peoplesoft Term cannot be blank.')]
    private $ps_strm = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'ps_session_code', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Peoplesoft Session Code cannot be blank.')]
    private $ps_session_code = '';

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_class_section")
     */
    #[ORM\Column(name: 'ps_class_section', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Peoplesoft Class Section cannot be blank.')]
    private $ps_class_section = '';

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_class_nbr")
     */
    #[ORM\Column(name: 'ps_class_nbr', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Peoplesoft Class Number cannot be blank.')]
    private $ps_class_nbr = '';

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_campus")
     */
    #[ORM\Column(name: 'ps_campus', type: 'string', length: 255, nullable: true)]
    private $ps_campus;

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_srr_component")
     */
    #[ORM\Column(name: 'ps_srr_component', type: 'string', length: 255, nullable: true)]
    private $ps_srr_component;

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_class_stat")
     */
    #[ORM\Column(name: 'ps_class_stat', type: 'string', length: 255, nullable: true)]
    private $ps_class_stat;

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_lms_url")
     */
    #[ORM\Column(name: 'ps_lms_url', type: 'string', length: 255, nullable: true)]
    private $ps_lms_url;

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_location")
     */
    #[ORM\Column(name: 'ps_location', type: 'string', length: 255, nullable: true)]
    private $ps_location;

    /**
     * @var string
     *
     * @Serializer\SerializedName("ps_class_descr")
     */
    #[ORM\Column(name: 'ps_class_descr', type: 'text')]
    #[Assert\NotBlank(message: 'Peoplesoft Class Description cannot be blank.')]
    private $ps_class_descr = '';

    /**
     * @Serializer\Exclude
     **/
    private $serialiseTasksWithSubtasksOnly = FALSE;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \VanillaProgrammeGroup::class, mappedBy: 'course', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $coursegroup;

    /**
     * @var string
     */
    #[ORM\Column(name: 'original_country', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Country should be selected.')]
    protected $original_country;

    /**
     * @var string
     */
    #[ORM\Column(name: 'original_timezone', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Timezone should be selected.')]
    protected $original_timezone;


     /**
     * @var integer
     *
     *
     */
    #[ORM\Column(name: 'course_type_view', type: 'integer')]
    protected $course_type_view;

    public function showOnlyTasksWithSubtasks($flag)
    {
        $this->serialiseTasksWithSubtasksOnly = $flag;
    }

    /**
     * Add a user to this course
     *
     * @param CourseSubscription
     *
     * @return Course
     */
    public function addUser(CourseSubscription $courseSubscription)
    {
        if (!$this->courseSubscriptions->contains($courseSubscription)) {
            $this->courseSubscriptions->add($courseSubscription);
        }

        return $this;
    }

    /**
     * Remove a user from this course
     *
     * @param CourseSubscription
     *
     * @return Course
     */
    public function removeUser(CourseSubscription $courseSubscription)
    {
        if ($this->courseSubscriptions->contains($courseSubscription)) {
            $this->courseSubscriptions->removeElement($courseSubscription);
        }

        return $this;
    }

    /**
     * Get list of Subscriptions
     *
     * @return ArrayCollection
     */
    public function getSubscriptions()
    {
        return $this->courseSubscriptions;
    }

    public function getSubscribersEntityByRole($roleName, $skipOnReadonly=false)
    {
        $users = [];

        if( $skipOnReadonly ) {
            //hide information peoplesoft ids if the user is accessing the programme with readonly access
            if (!$this->getProgramme()->checkIfMy() && !$this->getProgramme()->checkIfSuperAdmin()) {
                return [];
            }
        }

        if ($this->courseSubscriptions) {
            /** @var CourseSubscription $subscription */
            foreach ($this->courseSubscriptions->toArray() as $subscription) {
                if ($subscription->getRole()->getName() == $roleName) {
                    $users[] = $subscription->getUser();
                }
            }
        }

        return $users;
    }

    protected function getSubscribersByRole( $roleName, $skipOnReadonly=false ) {
        $users = [];

        if( $skipOnReadonly ) {
            //hide information peoplesoft ids if the user is accessing the programme with readonly access
            if (!$this->getProgramme()->checkIfMy() && !$this->getProgramme()->checkIfSuperAdmin()) {
                return [];
            }
        }

        if ($this->courseSubscriptions) {
            /** @var CourseSubscription $subscription */
            foreach ($this->courseSubscriptions->toArray() as $subscription) {
                if ($subscription->getRole()->getName() == $roleName) {
                    $users[] = $subscription->getUser()->getPeopleSoftId();
                }
            }
        }

        return $users;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("students")
     *
     * @param Boolean $returnsAnObject flag to determine if this function should return array of ids of objects
     *
     * @return array
     */
    public function getStudents( $returnsAnObject = false )
    {
        if( $returnsAnObject ) {
            return $this->getSubscribersEntityByRole('student',true);
        } else {
            return $this->getSubscribersByRole('student',true);
        }
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("professors")
     *
     * @param Boolean $returnsAnObject flag to determine if this function should return array of ids of objects
     *
     * @return array
     */
    public function getFaculty( $returnsAnObject = false )
    {
        if( $returnsAnObject ) {
            return $this->getSubscribersEntityByRole('faculty',true);
        } else {
            return $this->getSubscribersByRole('faculty',true);
        }
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("directors")
     *
     * @param Boolean $returnsAnObject flag to determine if this function should return array of ids of objects
     *
     * @return array
     */
    public function getDirectors( $returnsAnObject = false )
    {
        if( $returnsAnObject ) {
            return $this->getSubscribersEntityByRole('director',true);
        } else {
            return $this->getSubscribersByRole('director',true);
        }
    }

    /**
     * Include people from Guest, Programme Contacts
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("contacts")
     *
     * @param Boolean $returnsAnObject flag to determine if this function should return array of ids of objects
     *
     * @return array
     */
    public function getContacts( $returnsAnObject = false )
    {
        if( $returnsAnObject ) {
            return array_merge(
                $this->getSubscribersEntityByRole('contact',true),
                $this->getSubscribersEntityByRole('guest',true)
            );
        } else {
            return array_merge(
                $this->getSubscribersByRole('contact',true),
                $this->getSubscribersByRole('guest',true)
            );
        }
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("hidden")
     *
     * @param Boolean $returnsAnObject flag to determine if this function should return array of ids of objects
     *
     * @return array
     */
    public function getHidden( $returnsAnObject = false )
    {
        if( $returnsAnObject ) {
            return $this->getSubscribersEntityByRole('hidden',true);
        } else {
            return $this->getSubscribersByRole('hidden',true);
        }
    }

    /**
     * Include people from Coordinators & Programme Management
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("coordinations")
     *
     * @param Boolean $returnsAnObject flag to determine if this function should return array of ids of objects
     *
     * @return array
     */
    public function getCoordination( $returnsAnObject = false )
    {

        if( $returnsAnObject ) {
            return array_merge(
                $this->getSubscribersEntityByRole('coordinator',true),
                $this->getSubscribersEntityByRole('manager',true)
            );
        } else {
            return array_merge(
                $this->getSubscribersByRole('coordinator',true),
                $this->getSubscribersByRole('manager',true)
            );
        }
    }

    /**
     * Include people from Programme Advisor, Programme Consultant
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("esuiteteams")
     *
     * @param Boolean $returnsAnObject flag to determine if this function should return array of ids of objects
     *
     * @return array
     */
    public function getesuiteTeam( $returnsAnObject = false )
    {
        if( $returnsAnObject ) {
            return array_merge(
                $this->getSubscribersEntityByRole('advisor',true),
                $this->getSubscribersEntityByRole('consultant',true),
                $this->getSubscribersEntityByRole('esuiteteam',true)
            );
        } else {
            return array_merge(
                $this->getSubscribersByRole('advisor',true),
                $this->getSubscribersByRole('consultant',true),
                $this->getSubscribersByRole('esuiteteam',true)
            );
        }
    }

    /**
     * Get All users subscribed to a Course
     *
     * @return array
     */
    public function getAllUsers()
    {
        return [
            'students'     => $this->getStudents(),
            'professors'   => $this->getFaculty(),
            'directors'    => $this->getDirectors(),
            'contacts'     => $this->getContacts(),
            'hidden'       => $this->getHidden(),
            'esuiteteam'   => $this->getesuiteTeam(),
            'coordinators' => $this->getCoordination(),
        ];
    }


    /**
     * Get All User Objects subscribed to a Course
     *
     * @return array
     */
    public function getAllUserObjects()
    {
        return [
            'students'     => $this->getStudents(true),
            'professors'   => $this->getFaculty(true),
            'directors'    => $this->getDirectors(true),
            'contacts'     => $this->getContacts(true),
            'hidden'       => $this->getHidden(true),
            'esuiteteam'   => $this->getesuiteTeam(true),
            'coordinators' => $this->getCoordination(true),
        ];
    }


    /**
     * Get devices of all users subscribed to a Course
     *
     * @return array
     */
    public function getSubscribedUserDevices()
    {
        $subscribedUserDevices = [];
        /** @var CourseSubscription $subscription */
        foreach ($this->getSubscriptions()->toArray() as $subscription) {
            /** @var UserDevice $device */
            foreach ($subscription->getUser()->getUserDevices() as $device) {
                array_push($subscribedUserDevices, $device->getIosDeviceId());
            }
        }

        return $subscribedUserDevices;
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
     * @return Course
     */
    public function setId($id=null)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set programme
     *
     * @param Programme $programme
     * @return Course
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
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("programme_id")
     *
     * @return string
     */
    public function getProgrammeId()
    {
        return $this->getProgramme()->getId();
    }

    /**
     * Set uid
     *
     * @param string $uid
     *
     * @return Course
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get uid
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Course
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
     * Set abbreviation
     *
     * @param string $abbreviation
     *
     * @return Course
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    /**
     * Get abbreviation
     *
     * @return string
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * Set start_date
     *
     * @param \DateTime $startDate
     * @return Course
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
     * @return Course
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
     * Set timezone
     *
     * @param string $timezone
     * @return Course
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return Course
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set published
     *
     * @param boolean $published
     *
     * @return Course
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
     * Get list of Sessions
     *
     * @return array()
     */
    public function getSessions()
    {
        $sessions = [];
        if($this->sessions) {
            /** @var Session $session */
            foreach($this->sessions as $session) {
                //Check if published
                if($this->serializePublishedSubEntities && ($session->getPublished() === false)) {
                    continue;
                }
                array_push($sessions, $session);
            }
        }
        return $sessions;
    }

    /**
     * Get list of Published Sessions
     *
     * @return array()
     */
    public function getPublishedSessions()
    {
        $sessions = [];
        if($this->sessions) {
            /** @var Session $session */
            foreach ($this->sessions as $session) {
                if ($session->getPublished()) {
                    array_push($sessions, $session);
                }
            }
        }
        return $sessions;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("sessions")
     *
     * @return string
     */
    public function getSessionIds()
    {
        $sessions = [];
        if($this->sessions) {
            /** @var Session $session */
            foreach($this->sessions as $session) {
                // Check if only published sub-entities should be serialised
                if( $this->serializePublishedSubEntities && (!$session->getPublished()) ) continue;

                if($this->serializeFully) {
                    $session->serializeFullObject(true);
                    array_push($sessions, $session);
                } else {
                    array_push($sessions, $session->getId());
                }
            }
        }
        return $sessions;
    }

    /**
     * Get list of Activities
     *
     * @return array()
     */
    public function getActivities()
    {
        $activities = [];
        if($this->activities) {
            $activities = $this->activities->toArray();
        }
        return $activities;
    }

    /**
     * Get list of Published Activities
     *
     * @return array()
     */
    public function getPublishedActivities()
    {
        $activities = [];
        if($this->activities) {
            /** @var Activity $activity */
            foreach ($this->activities as $activity) {
                if ($activity->getPublished()) {
                    array_push($activities, $activity);
                }
            }
        }
        return $activities;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("activities")
     *
     * @return string
     */
    public function getActivityIds()
    {
        $activities = [];
        foreach($this->getActivities() as $activity) {
            // Check if only published sub-entities should be serialised
            if( $this->serializePublishedSubEntities && (!$activity->getPublished()) ) continue;

            if($this->serializeFully) {
                $activity->serializeFullObject(true);
                array_push($activities, $activity);
            } else {
                array_push($activities, $activity->getId());
            }

        }
        return $activities;
    }

    /**
     * Get box_group_id
     *
     * @return string
     */
    public function getBoxGroupId()
    {
        return $this->box_group_id;
    }

    /**
     * Set box_group_id
     *
     * @param string $box_group_id
     *
     * @return Course
     */
    public function setBoxGroupId($box_group_id)
    {
        $this->box_group_id = $box_group_id;

        return $this;
    }

    /**
     * Get list of Announcements
     *
     * @return array()
     */
    public function getAnnouncements()
    {
        $announcements = [];
        if( $this->announcements::class == \Doctrine\ORM\PersistentCollection::class
            || $this->announcements::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $announcements = $this->announcements->toArray();
        }

        return $announcements;
    }

    /**
     * Get list of Published Announcements
     *
     * @return array()
     */
    public function getPublishedAnnouncements()
    {
        $announcements = [];
        foreach ($this->getAnnouncements() as $announcement) {
            if ($announcement->getPublished()) {
                array_push($announcements, $announcement);
            }
        }

        return $announcements;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("announcements")
     *
     * @return array
     */
    public function getAnnouncementIds()
    {
        $announcements = [];
        if($this->getAnnouncements()) {
            foreach($this->getAnnouncements() as $announcement) {
                // Check if only published sub-entities should be serialised
                if( $this->serializePublishedSubEntities && (!$announcement->getPublished()) ) continue;

                if($this->serializeFully) {
                    $announcement->serializeFullObject(true);
                    array_push($announcements, $announcement);
                } else {
                    array_push($announcements, $announcement->getId());
                }
            }
        }

        return $announcements;
    }

    /**
     * Get list of all belonging Groups
     *
     * @return array()
     */
    public function getGroups()
    {
        $groups = [];
        if( $this->groups::class == \Doctrine\ORM\PersistentCollection::class
            || $this->groups::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $groups = $this->groups->toArray();
        }

        return $groups;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("groups")
     *
     * @return array
     */
    public function getGroupIds()
    {
        $groups = [];
        if($this->getGroups()) {
            foreach($this->getGroups() as $group) {
                // Check if only published sub-entities should be serialised
                if( $this->serializePublishedSubEntities ) continue;

                if($this->serializeFully) {
                    array_push($groups, $group);
                } else {
                    array_push($groups, $group->getId());
                }
            }
        }

        return $groups;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("default_group")
     *
     * @return string
     */
    public function getDefaultGroup()
    {
        if($this->getGroups()) {
            foreach($this->getGroups() as $group) {
                if($group->getCourseDefault()) {
                    return $group->getId();
                }
            }
        }

        return "";
    }

    /**
     * Get list of Tasks
     *
     * @return array()
     */
    public function getTasks()
    {
        $tasks = [];

        if($this->tasks && (count($this->tasks) > 0)) {

            /** @var Task $task */
            foreach($this->tasks as $task) {
                if($this->serialiseTasksWithSubtasksOnly) {
                    if($task->getSubtasks()) {
                        array_push($tasks, $task);
                    }
                } else {
                    array_push($tasks, $task);
                }
            }

            //manual sort the tasks by position
            $count = count($tasks);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    /** @var Task $aTask */
                    $aTask = $tasks[$i];

                    /** @var Task $bTask */
                    $bTask = $tasks[$j];

                    if ( $aTask->getPosition() > $bTask->getPosition() ) {
                        $temp = $tasks[$i];
                        $tasks[$i] = $tasks[$j];
                        $tasks[$j] = $temp;
                    }
                }
            }
            //manual sort the tasks by due date
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    /** @var Task $aTask */
                    $aTask = $tasks[$i];

                    /** @var Task $bTask */
                    $bTask = $tasks[$j];

                    if( $aTask->getDate() > $bTask->getDate() ) {
                        $temp = $tasks[$i];
                        $tasks[$i] = $tasks[$j];
                        $tasks[$j] = $temp;
                    }
                }
            }

            //manual sort the tasks unpublished last
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    /** @var Task $aTask */
                    $aTask = $tasks[$i];

                    $aOrder = 0;
                    if( !$aTask->getPublished() ) {
                        $aOrder = 1;
                    }

                    /** @var Task $bTask */
                    $bTask = $tasks[$j];

                    $bOrder = 0;
                    if( !$bTask->getPublished() ) {
                        $bOrder = 1;
                    }

                    if( $aOrder > $bOrder ) {
                        $temp = $tasks[$i];
                        $tasks[$i] = $tasks[$j];
                        $tasks[$j] = $temp;
                    }
                }
            }

        }

        return $tasks;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("tasks")
     *
     * @return array
     */
    public function getTaskIds()
    {
        $tasks = [];
        if($this->getTasks()) {
            foreach($this->getTasks() as $task) {
                if($this->serialiseTasksWithSubtasksOnly && (count($task->getSubtasks()) == 0) ) {
                    continue;
                }
                // Check if only published sub-entities should be serialised
                if( ($this->serializePublishedSubEntities && (!$task->getPublished())) ) {
                    continue;
                }
                if($this->serializeFully) {
                    $task->serializeFullObject(true);
                    array_push($tasks, $task);
                } else {
                    array_push($tasks, $task->getId());
                }
            }
        }

        return $tasks;
    }

    public function getSessionBoxFolders()
    {
        $boxFolders = [];
        if($this->getSessions()) {
            foreach($this->getSessions() as $session) {
                $boxFolders[] = $session->getBoxFolderId();
            }
        }

        if ($this->getTasks()) {
            foreach ($this->getTasks() as $task) {
                $boxFolders[] = $task->getBoxFolderId();
            }
        }

        return $boxFolders;
    }

    /**
     * Set ps_session_code
     *
     * @param string $psSessionCode
     * @return Course
     */
    public function setPsSessionCode($psSessionCode)
    {
        $this->ps_session_code = $psSessionCode;

        return $this;
    }

    /**
     * Get ps_session_code
     *
     * @return string
     */
    public function getPsSessionCode()
    {
        return $this->ps_session_code;
    }

    /**
     * Set ps_class_section
     *
     * @param string $psClassSection
     * @return Course
     */
    public function setPsClassSection($psClassSection)
    {
        $this->ps_class_section = $psClassSection;

        return $this;
    }

    /**
     * Get ps_class_section
     *
     * @return string
     */
    public function getPsClassSection()
    {
        return $this->ps_class_section;
    }

    /**
     * @return string
     */
    public function getPsCrseId()
    {
        return $this->ps_crse_id;
    }

    /**
     * @param string $ps_crse_id
     *
     * @return Course
     */
    public function setPsCrseId($ps_crse_id)
    {
        $this->ps_crse_id = $ps_crse_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsAcadCareer()
    {
        return $this->ps_acad_career;
    }

    /**
     * @param string $ps_acad_career
     *
     * @return Course
     */
    public function setPsAcadCareer($ps_acad_career)
    {
        $this->ps_acad_career = $ps_acad_career;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsStrm()
    {
        return $this->ps_strm;
    }

    /**
     * @param string $ps_strm
     *
     * @return Course
     */
    public function setPsStrm($ps_strm)
    {
        $this->ps_strm = $ps_strm;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsClassNbr()
    {
        return $this->ps_class_nbr;
    }

    /**
     * @param string $ps_class_nbr
     *
     * @return Course
     */
    public function setPsClassNbr($ps_class_nbr)
    {
        $this->ps_class_nbr = $ps_class_nbr;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsClassDescr()
    {
        return $this->ps_class_descr;
    }

    /**
     * @param string $ps_class_descr
     *
     * @return Course
     */
    public function setPsClassDescr($ps_class_descr)
    {
        $this->ps_class_descr = $ps_class_descr;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsCampus()
    {
        return $this->ps_campus;
    }

    /**
     * @param string $ps_campus
     *
     * @return Course
     */
    public function setPsCampus($ps_campus)
    {
        $this->ps_campus = $ps_campus;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsSrrComponent()
    {
        return $this->ps_srr_component;
    }

    /**
     * @param string $ps_srr_component
     *
     * @return Course
     */
    public function setPsSrrComponent($ps_srr_component)
    {
        $this->ps_srr_component = $ps_srr_component;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsClassStat()
    {
        return $this->ps_class_stat;
    }

    /**
     * @param string $ps_class_stat
     *
     * @return Course
     */
    public function setPsClassStat($ps_class_stat)
    {
        $this->ps_class_stat = $ps_class_stat;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsLmsUrl()
    {
        return $this->ps_lms_url;
    }

    /**
     * @param string $ps_lms_url
     *
     * @return Course
     */
    public function setPsLmsUrl($ps_lms_url)
    {
        $this->ps_lms_url = $ps_lms_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getPsLocation()
    {
        return $this->ps_location;
    }

    /**
     * @param string $ps_location
     *
     * @return Course
     */
    public function setPsLocation($ps_location)
    {
        $this->ps_location = $ps_location;

        return $this;
    }

    /**
     * Set original_timezone
     *
     * @param string $timezone
     * @return Course
     */
    public function setOriginalTimezone($timezone)
    {
        $this->original_timezone = $timezone;

        return $this;
    }

    /**
     * Get original_timezone
     *
     * @return string
     */
    public function getOriginalTimezone()
    {
        return $this->original_timezone;
    }

    /**
     * Set original_country
     *
     * @param string $country
     * @return Course
     */
    public function setOriginalCountry($country)
    {
        $this->original_country = $country;

        return $this;
    }

    /**
     * Get original_country
     *
     * @return string
     */
    public function getOriginalCountry()
    {
        return $this->original_country;
    }

     /**
     * @return int
     */
    public function getCourseTypeView()
    {
        return $this->course_type_view;
    }

    /**
     * @param int $course_type_view
     *
     * @return Course
     */
    public function setCourseTypeView($course_type_view)
    {
        $this->course_type_view = $course_type_view;

        return $this;
    }

    public function getLearningJourney()
    {
        return $this->learning_journey;
    }

    /**
     * This function handles the cloning of course and set the initial value
     * This will not persist the data
     *
     */
    public function __clone()
    {
        $prevID = $this->getId();
        $this->setId();
        $this->setDefaults();
        $this->setPublished(false);

        $currentDateTime = new \DateTime();
        $this->setCreated($currentDateTime);
        $this->setUpdated($currentDateTime);

        $abbrArray = explode("_", $this->getAbbreviation());
        if (count($abbrArray) > 1) array_shift($abbrArray);
        $newAbbr = implode('_', $abbrArray);
        $newAbbr = mktime(date("H"))."_".$newAbbr;
        $this->setAbbreviation($newAbbr);

        $newUID = "C-".$newAbbr."-".date("YmdHis").microtime(true)."_$prevID";
        $this->setUid($newUID);

        $this->setBoxGroupId("S3-".mktime(date("H")));

        $this->setPsClassNbr('---');
        $this->setPsCrseId('---');
        $this->setPsStrm('---');
    }
}
