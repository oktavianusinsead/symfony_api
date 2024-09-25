<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

use DateTime;

/**
 * Programme
 *
 *
 * @OA\Schema()
 */
#[ORM\Table(name: 'programmes')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Programme extends Base
{

    /**
     * 22 months before allowing to archive
     * @var int
     *
     * @Serializer\Exclude
     */
    private $archiveMonthsLimit = 22;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults();
    }

    private function setDefaults() {
        $this->archived                     = false;
        $this->courses                      = new ArrayCollection();
        $this->programmeAdmins              = new ArrayCollection();
        $this->courseSubscriptions          = new ArrayCollection();
        $this->coreUserGroup                = new ArrayCollection();
        $this->programmediscussions         = new ArrayCollection();
        $this->programmegroups              = new ArrayCollection();
        $this->programmeVanillaConversation = new ArrayCollection();
        $this->view_type                    = 3;
    }

    /**
     * @var integer
     *
     *
     * @OA\Property(description="The unique identifier of the Programme.")
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string
     *
     *
     * @OA\Property(description="The title/name of the Programme.")
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    private $name;

    /**
     * @var string
     *
     *
     * @OA\Property(description="The code of the Programme.")
     */
    #[ORM\Column(name: 'code', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Code cannot be blank.')]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'welcome', type: 'text')]
    #[Assert\NotBlank(message: 'Welcome message cannot be blank.')]
    #[Assert\Length(max: 3000, maxMessage: 'Welcome message cannot be longer than {{ limit }} characters.')]
    private $welcome;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean')]
    private $published = FALSE;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'link_webmail', type: 'boolean')]
    private $link_webmail = FALSE;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'link_yammer', type: 'boolean')]
    private $link_yammer = FALSE;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'link_myinsead', type: 'boolean')]
    private $link_myinsead = FALSE;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'link_amphihq', type: 'boolean', nullable: true)] // @Serializer\SerializedName("link_amphihq")
    private $link_amphihq = FALSE;

    /**
     * @var boolean
     *
     * @Serializer\SerializedName("link_faculty_blog")
     */
    #[ORM\Column(name: 'faculty_blogs', type: 'boolean', nullable: true)]
    private $faculty_blogs = FALSE;

    /**
     * @var boolean
     *
     * @Serializer\SerializedName("link_knowledge")
     */
    #[ORM\Column(name: 'insead_knowledge', type: 'boolean', nullable: true)]
    private $insead_knowledge = FALSE;

    /**
     * @var boolean
     *
     * @Serializer\SerializedName("link_learninghub")
     */
    #[ORM\Column(name: 'link_learninghub', type: 'boolean', nullable: true, options: ['default' => true])]
    private $link_learninghub = TRUE;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'private', type: 'boolean')]
    private $private = FALSE;

    /**
     * @var boolean
     *
     * @Serializer\SerializedName("starts_on_sunday")
     */
    #[ORM\Column(name: 'starts_on_sunday', type: 'boolean')]
    private $starts_on_sunday = FALSE;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'company_logo', type: 'boolean')]
    private $companyLogo = FALSE;

       /**
     * @var integer
     */
    #[ORM\Column(name: 'company_logo_size', type: 'integer')]
    private $companyLogoSize = 0;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'with_discussion', type: 'boolean')]
    private $withDiscussions = FALSE;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'discussions_publish', type: 'boolean')]
    private $discussionsPublish = FALSE;

    /**
     * @var \DateTime
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'book_timestamp_full', type: 'datetime', nullable: true)]
    private $bookTimestampFull;

    /**
     * @var \DateTime
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'book_timestamp_business', type: 'datetime', nullable: true)]
    private $bookTimestampBusiness;


    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Course::class, mappedBy: 'programme', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $courses;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \ProgrammeAdministrator::class, mappedBy: 'programme', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $programmeAdmins;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \CourseSubscription::class, mappedBy: 'programme', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $courseSubscriptions;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \ProgrammeUser::class, mappedBy: 'programme', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $coreUserGroup;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \VanillaProgrammeDiscussion::class, mappedBy: 'programme', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $programmediscussions;

    /**
     * @Serializer\Exclude
     **/
    private $hideCourses = false;

    /**
     * @Serializer\Exclude
     **/
    private $subscribedCourses = [];

    /**
     * @Serializer\Exclude
     **/
    private $serialiseOnlySubscribedCourses = false;

    /**
     * @Serializer\Exclude
     **/
    private $requestorId = '';

    /**
     * @Serializer\Exclude
     **/
    private $requestorScope = '';

    /**
     * @Serializer\Exclude
     **/
    private $forStaff = true;

    /**
     * @Serializer\Exclude
     **/
    private $forParticipant = false;

    /**
     * @Serializer\Exclude
     **/
    private $includeHidden = false;

    /**
     * @Serializer\Exclude
     **/
    private $overrideReadonly = false;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \VanillaProgrammeGroup::class, mappedBy: 'programme', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $programmegroups;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \VanillaConversation::class, mappedBy: 'programme', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $programmeVanillaConversation;

    /**
     * @var boolean
     *
     * @Serializer\SerializedName("archived_status")
     */
    #[ORM\Column(name: 'archived', type: 'boolean')]
    private $archived = FALSE;

    /**
     * @var \DateTime
     *
     * @Serializer\SerializedName("archived_date")
     */
    #[ORM\Column(name: 'archived_date', type: 'datetime', nullable: true)]
    private $archived_date;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'view_type', type: 'integer', options: ['default' => '3'])]
    private $view_type = 3;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'learning_journey', type: 'boolean')]
    private $learning_journey = FALSE;

    public function setHideCourses($hide)
    {
        $this->hideCourses = $hide;
    }

    public function setRequestorId($id)
    {
        $this->requestorId = $id;
    }
    public function setRequestorScope($scope)
    {
        $this->requestorScope = $scope;
    }

    public function setForStaff($forStaff)
    {
        $this->forStaff = $forStaff;
    }
    public function setForParticipant($forParticipant)
    {
        $this->forParticipant = $forParticipant;
    }
    public function setIncludeHidden($includeHidden)
    {
        $this->includeHidden = $includeHidden;
    }
    public function setOverriderReadonly($overrideReadonly)
    {
        $this->overrideReadonly = $overrideReadonly;
    }


    /**
     * Get programmeAdmins
     *
     * @return ProgrammeAdministrator
     */
    public function getProgrammeAdministrators()
    {
        return $this->programmeAdmins;
    }

    /**
     * Get courseSubscription
     *
     * @return CourseSubscription
     */
    public function getCourseSubscriptions()
    {
        return $this->courseSubscriptions;
    }

    /**
     * Get Programme Core Group
     *
     * @return ProgrammeUser
     */
    public function getProgrammeCoreGroup()
    {
        return $this->coreUserGroup;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return Programme
     */
    public function setId($id=null)
    {
        $this->id = $id;
        return $this;
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
     * Set name
     *
     * @param string $name
     * @return Programme
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
     * Set code
     *
     * @param string $code
     * @return Programme
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set welcome
     *
     * @param string $welcome
     * @return Programme
     */
    public function setWelcome($welcome)
    {
        $this->welcome = $welcome;

        return $this;
    }

    /**
     * Get welcome
     *
     * @return string
     */
    public function getWelcome()
    {
        return $this->welcome;
    }

    /**
     * Set published
     *
     * @param boolean $published
     * @return Programme
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
     * Set link_webmail
     *
     * @param boolean $linkWebmail
     * @return Programme
     */
    public function setLinkWebmail($linkWebmail)
    {
        $this->link_webmail = $linkWebmail;

        return $this;
    }

    /**
     * Get link_webmail
     *
     * @return boolean
     */
    public function getLinkWebmail()
    {
        return $this->link_webmail;
    }

    /**
     * Set link_yammer
     *
     * @param boolean $linkYammer
     * @return Programme
     */
    public function setLinkYammer($linkYammer)
    {
        $this->link_yammer = $linkYammer;

        return $this;
    }

    /**
     * Get link_yammer
     *
     * @return boolean
     */
    public function getLinkYammer()
    {
        return $this->link_yammer;
    }

    /**
     * Set link_myinsead
     *
     * @param boolean $linkMyinsead
     * @return Programme
     */
    public function setLinkMyinsead($linkMyinsead)
    {
        $this->link_myinsead = $linkMyinsead;

        return $this;
    }

    /**
     * Get link_myinsead
     *
     * @return boolean
     */
    public function getLinkMyinsead()
    {
        return $this->link_myinsead;
    }

    /**
     * Set link_amphihq
     *
     * @param boolean $linkAmphiHq
     * @return Programme
     */
    public function setLinkAmphiHq($linkAmphiHq)
    {
        $this->link_amphihq = $linkAmphiHq;

        return $this;
    }

    /**
     * Get link_amphihq
     *
     * @return boolean
     */
    public function getLinkAmphiHq()
    {
        return $this->link_amphihq;
    }

    /**
     * Set faculty_blogs
     *
     * @param boolean $faculty_blogs
     * @return Programme
     */
    public function setFacultyBlogs($faculty_blogs)
    {
        $this->faculty_blogs = $faculty_blogs;

        return $this;
    }

    /**
     * Get faculty_blogs
     *
     * @return boolean
     */
    public function getFacultyBlogs()
    {
        return $this->faculty_blogs;
    }

    /**
     * Set insead_knowledge
     *
     * @param boolean $insead_knowledge
     * @return Programme
     */
    public function setInseadKnowledge($insead_knowledge)
    {
        $this->insead_knowledge = $insead_knowledge;

        return $this;
    }

    /**
     * Get insead_knowledge
     *
     * @return boolean
     */
    public function getInseadKnowledge()
    {
        return $this->insead_knowledge;
    }

    /**
     * Set private
     *
     * @param boolean $private
     * @return Programme
     */
    public function setPrivate($private)
    {
        $this->private = $private;

        return $this;
    }

    /**
     * Get private
     *
     * @return boolean
     */
    public function getPrivate()
    {
        return $this->private;
    }

    /**
     * Set starts_on_sunday
     *
     * @param boolean $starts_on_sunday
     * @return Programme
     */
    public function setStartsOnSunday($starts_on_sunday)
    {
        $this->starts_on_sunday = $starts_on_sunday;

        return $this;
    }

    /**
     * Get starts_on_sunday
     *
     * @return boolean
     */
    public function getStartsOnSunday()
    {
        return $this->starts_on_sunday;
    }

    /**
     * Set companyLogo
     *
     * @param boolean $companyLogo
     * @return Programme
     */
    public function setCompanyLogo($companyLogo)
    {
        $this->companyLogo = $companyLogo;

        return $this;
    }

    /**
     * Get companyLogo
     *
     * @return boolean
     */
    public function getCompanyLogo()
    {
        return $this->companyLogo;
    }

    /**
     * @return integer
     */
    public function getCompanyLogoSize()
    {
        return $this->companyLogoSize;
    }

    /**
     * @param integer $companyLogoSize
     *
     * @return Programme
     */
    public function setCompanyLogoSize($companyLogoSize)
    {
        $this->companyLogoSize = $companyLogoSize;

        return $this;
    }

    /**
     * Set withDiscussions
     *
     * @param boolean $withDiscussions
     * @return Programme
     */
    public function setWithDiscussions($withDiscussions)
    {
        $this->withDiscussions = $withDiscussions;

        return $this;
    }

    /**
     * Get withDiscussions
     *
     * @return boolean
     */
    public function getWithDiscussions()
    {
        return $this->withDiscussions;
    }

    /**
     * Set DiscussionsPublish
     *
     * @param boolean $discussionsPublish
     * @return Programme
     */
    public function setDiscussionsPublish($discussionsPublish)
    {
        $this->discussionsPublish = $discussionsPublish;

        return $this;
    }

    /**
     * Get DiscussionsPublish
     *
     * @return bool
     */
    public function getDiscussionsPublish()
    {
        return $this->discussionsPublish;
    }

    /**
     * Set view type
     *
     * @param $viewType
     * @return Programme
     */
    public function setViewType($viewType)
    {
        $this->view_type = $viewType;

        return $this;
    }

    /**
     * Get view type
     *
     * @return integer
     */
    public function getViewType()
    {
        return $this->view_type;
    }

    /**
     * Set learning_journey
     *
     * @param boolean $learning_journey
     * @return Programme
     */
    public function setLearningJourney($learning_journey)
    {
        $this->learning_journey = $learning_journey;

        return $this;
    }

    /**
     * Get learning_journey
     *
     * @return boolean
     */
    public function getLearningJourney()
    {
        return $this->learning_journey;
    }
    
    /**
     * Get list of Courses
     *
     * @return array()
     */
    public function getCourses()
    {
        $courses = [];
        if($this->courses) {

            /** @var Course $course */
            foreach($this->courses as $course) {
                //Check if published
                if($this->serializePublishedSubEntities && ($course->getPublished() === false)) {
                    continue;
                }
                array_push($courses, $course);
            }
        }
        return $courses;
    }

     /**
     * Get list of Published Courses
     *
     * @return array()
     */
    public function getPublishedCourses()
    {
        $courses = [];
        if($this->courses) {
            foreach ($this->getCourses() as $course) {
                if ($course->getPublished()) {
                    array_push($courses, $course);
                }
            }
        }
        return $courses;
    }

    public function showOnlySubscribedCourses($courseIds)
    {
        $this->serialiseOnlySubscribedCourses = TRUE;
        $this->subscribedCourses = array_merge($this->subscribedCourses, $courseIds);
        return $this;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("courses")
     *
     * @return array()
     */
    public function getCoursesIds()
    {
        $courses = [];
        if(!$this->hideCourses) {
            if($this->courses) {
                foreach ($this->getCourses() as $course) {
                    if ($this->serializeFully) {
                        $course->serializeFullObject(TRUE);
                        array_push($courses, $course);
                    } else {
                        //Check if the course needs to be hidden
                        if($this->serialiseOnlySubscribedCourses) {
                            if(in_array($course->getId(), $this->subscribedCourses)) {
                                // If not, make it serializable
                                array_push($courses, $course->getId());
                            }
                        } else {
                            array_push($courses, $course->getId());
                        }

                    }
                }
            }
        }
        return $courses;
    }

    /**
     * Get start_date
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("start_date")
     *
     * @param boolean $publishedOnly
     *
     * @return \DateTime
     */
    public function getStartDate($publishedOnly=false)
    {
        $start_date = NULL;
        $dates = [];
        if($this->courses) {
            /** @var Course $course */
            foreach($this->courses as $course) {
                if ($publishedOnly) {
                    if ($course->getPublished())
                        array_push($dates, $course->getStartDate());
                } else {
                    array_push($dates, $course->getStartDate());
                }
            }
            $start_date = $this->getEarliestDate($dates);
        }
        return $start_date;
    }

    /**
     * Gets the earliest date from an array of \DateTime Objects
     *
     * @param array() $dates
     * @return \DateTime
     */
    private function getEarliestDate($dates)
    {
        $earliestDate = null;

        /** @var \DateTime $date */
        foreach($dates as $date){
            if($earliestDate) {

                /** @var \DateTime $earliestDate */
                if ($date->format('U') < $earliestDate->format('U')) {
                    $earliestDate = $date;
                }
            } else {
                $earliestDate = $date;
            }
        }
        return $earliestDate;
    }




    /**
     * Get start_date_timeZone
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("start_date_timezone")
     *
     * @return \DateTime
     */
    public function getStartDateTimeZone()
    {
        $start_date_timezone = NULL;
        $dates = [];
        if($this->courses) {
            /** @var Course $course */
            foreach($this->courses as $course) {
                array_push($dates,
                    ["startDate" => $course->getStartDate(), "timezone" => $course->getTimezone()]);
            }
            $start_date_timezone = $this->getEarliestTimezone($dates);

        return $start_date_timezone ? $start_date_timezone['timezone'] : NULL;
        }
    }


        /**
         * Gets the earliest date from an array of \DateTime Objects
         *
         * @param array() $dates
         * @return \String
         */
        private function getEarliestTimezone($dates)
    {
        $earliestDateObj = null;

        /** @var \DateTime $date */
        foreach($dates as $date){

            if($earliestDateObj) {

                /** @var \DateTime $earliestDate */
                if ($date['startDate']->format('U') < $earliestDateObj['startDate']->format('U')) {
                    $earliestDateObj = $date;
                }
            } else {
                $earliestDateObj = $date;
            }
        }
        return $earliestDateObj;
    }


        /**
     * Get end_date
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("end_date")
     *
     * @param booleoan $publishedOnly
     *
     * @return \DateTime
     */
    public function getEndDate($publishedOnly=false)
    {
        $end_date = null;
        $dates = [];
        if($this->courses) {
            /** @var Course $course */
            foreach($this->courses as $course) {
                if ($publishedOnly) {
                    if ($course->getPublished())
                        $dates[] = $course->getEndDate();
                } else {
                    $dates[] = $course->getEndDate();
                }
            }
            $end_date = $this->getLatestDate($dates);
        }
        return $end_date;
    }

    /**
     * Gets the latest date from an array of \DateTime Objects
     *
     * @param array() $dates
     * @return \DateTime
     */
    private function getLatestDate($dates)
    {
        $latestDate = null;

        /** @var \DateTime $date */
        foreach($dates as $date){
            if($latestDate) {

                /** @var \DateTime $latestDate */
                if ($date->format('U') > $latestDate->format('U')) {
                    $latestDate = $date;
                }
            } else {
                $latestDate = $date;
            }
        }
        return $latestDate;
    }

    /**
     * Get end_date
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("end_date_timezone")
     *
     * @return \DateTime
     */
    public function getEndDateTimezone()
    {
        $end_date_timezone = NULL;
        $dates = [];
        if($this->courses) {
            /** @var Course $course */
            foreach($this->courses as $course) {
                array_push($dates,
                    ["endDate" => $course->getEndDate(), "timezone" => $course->getTimezone()]);
            }
            $end_date_timezone = $this->getLateDateTimeZone($dates);

            return $end_date_timezone ? $end_date_timezone['timezone'] : NULL;
        }
    }

    /**
     * Gets the latest date from an array of \DateTime Objects
     *
     * @param array() $dates
     * @return \DateTime
     */
    private function getLateDateTimeZone($dates)
    {
        $latestDateObj = null;

        /** @var \DateTime $date */
        foreach($dates as $date){
            if($latestDateObj) {

                /** @var \DateTime $latestDate */
                if ($date['endDate']->format('U') > $latestDateObj['endDate']->format('U')) {
                    $latestDateObj = $date;
                }
            } else {
                $latestDateObj = $date;
            }
        }
        return $latestDateObj;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("core_group")
     *
     * @return array()
     */
    public function getCoreGroupPSoftIds()
    {
        $coreGroup = [[],[]]; //holds 2-layer array

        $programmeCoreGroup = $this->getProgrammeCoreGroup();
        $coreGroupCnt = count($programmeCoreGroup);

        $maxRows = 2;
        $maxCols = 3;

        //generate from database
        if( $coreGroupCnt ) {
            if ($programmeCoreGroup) {
                for ($i = 0; $i < $maxRows; $i++) {
                    for ($j = 0; $j < $maxCols; $j++) {
                        /** @var ProgrammeUser $programmeUser */
                        foreach ($programmeCoreGroup as $programmeUser) {
                            $rowIndex = $programmeUser->getRowIndex() - 1;
                            $orderIndex = $programmeUser->getOrderIndex() - 1;

                            if ($i == $rowIndex && $j == $orderIndex) {
                                array_push($coreGroup[$rowIndex], $programmeUser->getUser()->getPeoplesoftId());
                                break;
                            }
                        }
                    }
                }
            }

        //autogenerate based on published course directors and coordinators
        } else {
            $courses = $this->getPublishedCourses();

            $directors = [];
            $coordinators = [];

            //get directors and coordinators from the courses
            /** @var Course $course */
            foreach($courses as $course) {
                $courseDirectors = $course->getDirectors();
                $courseCoordinators = $course->getCoordination();

                foreach($courseDirectors as $courseDirector) {
                    $directors[ $courseDirector ] = $courseDirector;
                }
                foreach($courseCoordinators as $courseCoordinator) {
                    $coordinators[ $courseCoordinator ] = $courseCoordinator;
                }
            }

            $rowIndex = 0;
            foreach( $directors as $director ) {
                if( $rowIndex < $maxRows ) {
                    array_push($coreGroup[$rowIndex], $director);

                    if( count( $coreGroup[$rowIndex] ) >= $maxCols ) {
                        $rowIndex = $rowIndex + 1;
                    }
                }
            }

            foreach( $coordinators as $coordinator ) {
                if( $rowIndex < $maxRows ) {
                    if (!isset($directors[$coordinator])) {
                        array_push($coreGroup[$rowIndex], $coordinator);

                        if (count($coreGroup[$rowIndex]) >= $maxCols) {
                            $rowIndex = $rowIndex + 1;
                        }
                    }
                }
            }
        }

        return $coreGroup;
    }

    /**
     * Checks if the programme "is my"
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_my")
     *
     * @return boolean
     */
    public function checkIfMy()
    {
        $myRoles = [];
        if( $this->forParticipant ) {
            array_push($myRoles,'student');
        }
        if( $this->forStaff ) {
            array_push($myRoles,'coordinator');
            array_push($myRoles,'faculty');
            array_push($myRoles,'director');
            array_push($myRoles,'advisor');
            array_push($myRoles,'manager');
            array_push($myRoles,'contact');
            array_push($myRoles,'consultant');
            array_push($myRoles,'guest');
            array_push($myRoles,'inseadteam');
        }
        if( $this->includeHidden ) {
            array_push($myRoles,'hidden');
        }

        $isMy = false;

        if($this->programmeAdmins) {
            /** @var ProgrammeAdministrator $admin */
            foreach( $this->programmeAdmins as $admin ) {
                if( $admin->getUser()->getId() == $this->requestorId ) {
                    $isMy = true;

                    break;
                }
            }
        }

        if( !$isMy ) {
            if($this->courseSubscriptions) {
                /** @var CourseSubscription $subscription */
                foreach($this->courseSubscriptions as $subscription) {
                    foreach( $myRoles as $validRole ) {
                        if(
                            ( $subscription->getUser()->getId() == $this->requestorId )
                            && ($subscription->getRole()->getName() == $validRole)
                        ) {
                            $isMy = true;

                            break;
                        }
                    }

                    if( $isMy ) {
                        break;
                    }
                }
            }
        }

        if( $this->overrideReadonly ) {
            $isMy = true;
        }

        return $isMy;
    }

    /**
     * Checks if the user "is owner" of the programme
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_owner")
     *
     * @return boolean
     */
    public function checkIfOwner()
    {
        $isOwner = false;

        if($this->programmeAdmins) {
            /** @var ProgrammeAdministrator $admin */
            foreach( $this->programmeAdmins as $admin ) {
                if( $admin->getUser()->getId() == $this->requestorId ) {
                    $isOwner = $admin->getOwner();

                    break;
                }
            }
        }

        return $isOwner;
    }

    /**
     * Checks if the user "is super" of the programme
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_super")
     *
     * @return boolean
     */
    public function checkIfSuperAdmin()
    {
        return ($this->requestorScope == "studysuper" || $this->requestorScope == "studyssvc");
    }

    /**
     * checks if the programme "is completed"
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_completed")
     *
     * @return boolean
     */
    public function checkIfCompleted()
    {
        $isCompleted = true;
        $now = new \DateTime();

        if ($this->checkIfLive()) return false;
        if ($this->checkIfPending()) return false;

        if( count($this->courses) && $this->courses ) {
            /** @var Course $course */
            foreach($this->courses as $course) {
                if( $isCompleted && $course->getStartDate() < $now && $course->getEndDate() < $now ) {
                    $isCompleted = true;
                } else {
                    $isCompleted = false;
                }
            }
        } else {
            $isCompleted = false;
        }

        return $isCompleted;
    }

    /**
     * checks if the programme "is pending"
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_pending")
     *
     * @return boolean
     */
    public function checkIfPending()
    {
        $isPending = false;
        $now = new \DateTime();

        if ($this->checkIfLive()) return false;

        if( count($this->courses) && $this->courses ) {
            /** @var Course $course */
            foreach($this->courses as $course) {
                if( $course->getStartDate() > $now && $course->getEndDate() > $now ) {
                    $isPending = true;

                    break;
                }
            }
        } else {
            $isPending = true;
        }

        return $isPending;
    }


    /**
     * checks if the programme "is live"
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_live")
     *
     * @return boolean
     */
    public function checkIfLive()
    {
        $isLive = false;
        $now = new \DateTime();

        if( count($this->courses) && $this->courses ) {
            /** @var Course $course */
            foreach($this->courses as $course) {
                if( $course->getStartDate() <= $now && $course->getEndDate() >= $now ) {
                    $isLive = true;

                    break;
                }
            }
        }

        return $isLive;
    }

    /**
     * Checks if the programme "is my"
     * @Serializer\VirtualProperty
     * @Serializer\Exclude
     *
     * @return \DateTime
     */
    public function getLatestUpdatedPersonTimestamp()
    {
        $myRoles = [];
        if( $this->forParticipant ) {
            array_push($myRoles,'student');
        }
        if( $this->forStaff ) {
            array_push($myRoles,'coordinator');
            array_push($myRoles,'faculty');
            array_push($myRoles,'director');
            array_push($myRoles,'advisor');
            array_push($myRoles,'manager');
            array_push($myRoles,'contact');
            array_push($myRoles,'consultant');
            array_push($myRoles,'guest');
            array_push($myRoles,'inseadteam');
        }
        if( $this->includeHidden ) {
            array_push($myRoles,'hidden');
        }

        $lastUpdate = '';

        if($this->courseSubscriptions) {
            /** @var CourseSubscription $subscription */
            foreach($this->courseSubscriptions as $subscription) {
                foreach( $myRoles as $validRole ) {
                    if( $subscription->getRole()->getName() == $validRole ) {

                        if( $lastUpdate == "" ) {
                            $lastUpdate = $subscription->getUser()->getProfileLastUpdated();
                        } else if( $subscription->getUser()->getProfileLastUpdated() > $lastUpdate ) {
                            $lastUpdate = $subscription->getUser()->getProfileLastUpdated();
                        }

                    }
                }

            }
        }

        return $lastUpdate;
    }

    /**
     * Get List of Programme team members
     * @Serializer\VirtualProperty
     * @Serializer\Exclude
     *
     * @return array
     */
    public function getProgrammeTeam()
    {
        $myRoles = [];
        array_push($myRoles,'coordinator');
        array_push($myRoles,'faculty');
        array_push($myRoles,'director');
        array_push($myRoles,'advisor');
        array_push($myRoles,'manager');
        array_push($myRoles,'contact');
        array_push($myRoles,'consultant');
        array_push($myRoles,'guest');
        array_push($myRoles,'hidden');
        array_push($myRoles,'inseadteam');

        $programmeTeam = [];

        if($this->courseSubscriptions) {
            /** @var CourseSubscription $subscription */
            foreach($this->courseSubscriptions as $subscription) {

                foreach( $myRoles as $validRole ) {
                    if( $subscription->getRole()->getName() == $validRole ) {

                        if( array_search( $subscription->getUser()->getPeoplesoftId(), $programmeTeam ) === false ) {
                            array_push( $programmeTeam, $subscription->getUser()->getPeoplesoftId() );
                        }

                    }
                }

            }
        }

        return $programmeTeam;
    }

    /**
     * Set BookTimeStamp Full
     *
     * @param \DateTime $bookTimestampFull
     * @return Base
     */
    public function setBookTimestampFull($bookTimestampFull)
    {
        $this->bookTimestampFull = $bookTimestampFull;

        return $this;
    }

    /**
     * Get BookTimeStamp Full
     *
     * @return \DateTime
     */
    public function getBookTimestampFull()
    {
        return $this->bookTimestampFull;
    }

    /**
     * Set BookTimeStamp Business
     *
     * @param \DateTime $bookTimestampBusiness
     * @return Base
     */
    public function setBookTimestampBusiness($bookTimestampBusiness)
    {
        $this->bookTimestampBusiness = $bookTimestampBusiness;

        return $this;
    }

    /**
     * Get BookTimeStamp Business
     *
     * @return \DateTime
     */
    public function getBookTimestampBusiness()
    {
        return $this->bookTimestampBusiness;
    }

    /**
     * Set Archive Date
     *
     * @param \DateTime $archived_date
     * @return Programme
     */
    public function setArchiveDate($archived_date)
    {
        $this->archived_date = $archived_date;

        return $this;
    }

    /**
     * Get Archive Date
     *
     * @return \DateTime
     */
    public function getArchiveDate()
    {
        return $this->archived_date;
    }

    /**
     * Set Archive status
     *
     * @param boolean $archive
     * @return Programme
     */
    public function setArchived($archive)
    {
        $this->archived = $archive;

        return $this;
    }

    /**
     * Get Archive status
     *
     * @return boolean
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Get Archived Remaining Days
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("remaining_archive_days")
     *
     * @return int
     * @throws \Exception
     */
    public function getArchivedRemainingDays(){
        if ($this->archived){
            /** @var \DateTime $archived_date */
            $archived_date = $this->archived_date;

            $current_date = new \DateTime('00:00:00');

            return date_diff($current_date,$archived_date)->format("%r%a");
        } else {
            return -1;
        }
    }

    /**
     * Get is allowed to archive
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("is_allowed_archive")
     *
     * @return boolean
     * @throws \Exception
     */
    public function getAllowedToArchive(){

        /** @var \DateTime $programme_end_date */
        $programme_end_date = $this->getEndDate();
        if ($programme_end_date) {
            $programme_end_date->setTime(23, 0, 0);

            /** @var \DateTime $current_date */
            $current_date = new \DateTime('00:00:01');

            $date_diff = date_diff($programme_end_date, $current_date);
            $diff_months = ($date_diff->m + (12 * $date_diff->y));

            if ($diff_months >= $this->archiveMonthsLimit) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * This function handles the cloning of programme and set the initial value
     * This will not persist the data
     *
     */
    public function __clone()
    {
        $this->setId();
        $this->setDefaults();
        $this->setPublished(true);

        $currentDateTime = new \DateTime();
        $this->setCreated($currentDateTime);
        $this->setUpdated($currentDateTime);
        $this->setBookTimestampBusiness(null);
        $this->setBookTimestampFull(null);
        $this->setArchiveDate(null);
        $this->setArchived(false);

        $codeArray = explode('_copy_', $this->code);
        $this->setCode($codeArray[0]."_copy_".mktime(date("H")));
    }

    /**
     * @return Programme
     */
    public function setLinkLearninghub(bool $link_learninghub)
    {
        $this->link_learninghub = $link_learninghub;

        return $this;
    }

    /**
     * @return bool
     */
    public function getLinkLearninghub()
    {
        return $this->link_learninghub;
    }
}
