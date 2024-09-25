<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;


/**
 * Session
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: 'sessions')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Session extends Base
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
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'sessions')]
    private $course;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinTable(name: 'sessions_users')]
    #[ORM\ManyToMany(targetEntity: \User::class, inversedBy: 'userSessions', cascade: ['persist'])]
    protected $professors;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults();
    }

    private function setDefaults() {
        $this->group_sessions             = new ArrayCollection();
        $this->professors                 = new ArrayCollection();
        $this->videos                     = new ArrayCollection();
        $this->linkedDocuments            = new ArrayCollection();
        $this->fileDocuments              = new ArrayCollection();
        $this->links                      = new ArrayCollection();
        $this->group_sessions_attachments = new ArrayCollection();
        $this->pending_attachments        = new ArrayCollection();
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'uid', type: 'string', length: 255, unique: true)]
    private $uid;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Session Title cannot be blank.')]
    private $name;

    /**
     * @var string
     *
     * @Serializer\SerializedName("alternate_session_name")
     */
    #[ORM\Column(name: 'alternate_session_name', type: 'string', length: 255, nullable: true)]
    private $alternate_session_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text')]
    #[Assert\NotBlank(message: 'Description cannot be blank.')]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'abbreviation', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Session Code cannot be blank.')]
    private $abbreviation;

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
     * @var integer
     */
    #[ORM\Column(name: 'position', type: 'integer', nullable: true)]
    private $position = 0;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean')]
    private $published = FALSE;

    /**
     * @var string
     *
     * @Serializer\SerializedName("session_color")
     */
    #[ORM\Column(name: 'session_color', type: 'string', length: 10, options: ['default' => '#ffffff'])]
    private $session_color = "#ffffff";

    /**
     * @var string
     *
     * @Serializer\SerializedName("remarks")
     */
    #[ORM\Column(name: 'remarks', type: 'string', nullable: true)]
    private $remarks;

    /**
     * @var string
     *
     * @Serializer\SerializedName("optional_text")
     */
    #[ORM\Column(name: 'optional_text', type: 'text', nullable: true)]
    private $optional_text;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_scheduled', type: 'boolean', options: ['default' => 0])]
    private $is_scheduled = FALSE;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \LinkedDocument::class, mappedBy: 'session', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $linkedDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Video::class, mappedBy: 'session', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $videos;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Link::class, mappedBy: 'session', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $links;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \FileDocument::class, mappedBy: 'session', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $fileDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \GroupSession::class, mappedBy: 'session', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $group_sessions;

    /**
     * @Serializer\Exclude
     **/
    private $hideGroupSessions = FALSE;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \GroupSessionAttachment::class, mappedBy: 'session', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $group_sessions_attachments;

    /**
     * @Serializer\Exclude
     **/
    private $group_session_attachments_for = -1;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \PendingAttachment::class, mappedBy: 'session', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $pending_attachments;



    /**
     * @Serializer\Exclude
     **/
    #[ORM\Column(name: 'box_folder_id', type: 'string', length: 255)]
    private $box_folder_id;

    /**
     * @Serializer\Exclude
     **/
    private $serializeOnlyPublishedAttachments = FALSE;

    /**
     * @Serializer\Exclude
     **/
    private $showHandouts = FALSE;

    /**
     * @Serializer\Exclude
     **/
    private $latestHandout = null;

    /**
     * @Serializer\Exclude
     **/
    private $isWebView = FALSE;

    public function showHandouts($show)
    {
        $this->showHandouts = $show;
    }

    public function doNotShowGroupSessions($hide)
    {
        $this->hideGroupSessions = $hide;
    }

    public function checkGroupSessionAttachmentsFor($userid)
    {
        $this->group_session_attachments_for = $userid;

        return $this;
    }

    public function getLatestHandout()
    {
        return $this->latestHandout;
    }
    public function setLatestHandout($attachment)
    {
        $this->latestHandout = $attachment;

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
     * Set id
     *
     * @param integer $id
     * @return Session
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
     * @return Session
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
     * Set uid
     *
     * @param string $uid
     * @return Session
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get box_folder_id
     *
     * @return string
     */
    public function getBoxFolderId()
    {
        return $this->box_folder_id;
    }

    /**
     * Set box_folder_id
     *
     * @param string $box_folder_id
     * @return Session
     */
    public function setBoxFolderId($box_folder_id)
    {
        $this->box_folder_id = $box_folder_id;

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
     * @return Session
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
     * Set alternate_session_name
     *
     * @param string $alternate_session_name
     * @return Session
     */
    public function setAlternateSessionName($alternate_session_name)
    {
        $this->alternate_session_name = $alternate_session_name;

        return $this;
    }

    /**
     * Get alternate_session_name
     *
     * @return string
     */
    public function getAlternateSessionName()
    {
        return $this->alternate_session_name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Session
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
     * Set abbreviation
     *
     * @param string $abbreviation
     * @return Session
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
     * @param \DateTime $start_date
     * @return Session
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
     * @return Session
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
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     *
     * @return Session
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
     * @return Session
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
     * Set session_color
     *
     * @param string $session_color
     * @return Session
     */
    public function setSessionColor($session_color)
    {
        $this->session_color = $session_color;

        return $this;
    }

    /**
     * Get session_color
     *
     * @return string
     */
    public function getSessionColor()
    {
        return $this->session_color;
    }

    /**
     * Set optional_text
     *
     * @param string
     * @return Session
     */
    public function setOptionalText($optional_text)
    {
        $this->optional_text = $optional_text;

        return $this;
    }

    /**
     * Get optional_text
     *
     * @return string
     */
    public function getOptionalText()
    {
        return $this->optional_text;
    }

    /**
     * Set is_scheduled
     *
     * @param string
     * @return Session
     */
    public function setSessionScheduled($is_scheduled)
    {
        $this->is_scheduled = $is_scheduled;

        return $this;
    }

    /**
     * Get is_scheduled
     *
     * @return string
     */
    public function getSessionScheduled()
    {
        return $this->is_scheduled;
    }

    /**
     * Set remarks
     *
     * @param string
     * @return Session
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Add a Professor (user) to this course
     *
     * @return Session
     */
    public function addProfessor(User $professor)
    {
        if(!$this->professors->contains($professor)) {
            $this->professors->add($professor);
        }
        return $this;
    }

    /**
     * Remove a Professor (user) from this Session
     *
     * @return Session
     */
    public function removeProfessor(User $professor)
    {
        if($this->professors->contains($professor)) {
            $this->professors->removeElement($professor);
        }
        return $this;
    }

    /**
     * Get list of Professors assigned to this Session
     *
     * @return array()
     */
    public function getProfessors()
    {
        return $this->professors->toArray();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("professors")
     *
     * @return array
     */
    public function getProfessorList()
    {
        $professors = [];
        foreach($this->getProfessors() as $professor) {
            array_push($professors, $professor->getPeoplesoftId());
        }
        return $professors;
    }

    public function setSerializeOnlyPublishedAttachments()
    {
        $this->serializeOnlyPublishedAttachments = TRUE;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("attachments")
     *
     * @return string
     */
    public function getAttachmentList()
    {
        $allAttachments = array_merge($this->videos->toArray(), $this->linkedDocuments->toArray(), $this->links->toArray(), $this->fileDocuments->toArray());
        $serialisedAttachments = [];
        $attachments = [];

        $now = new \DateTime();

        if($this->serializeOnlyPublishedAttachments) {
            /** @var Attachment $a */
            foreach($allAttachments as $a) {
                //!!PV 20160513 added to check against Group Session Attachment table is there are values that state the file is publish at the group level
                $group_session_attachments = $a->getSession()->getGroupSessionAttachments();
                $group_session_ids = $this->findGroupSessionsForUser();

                if($a->getPublishAt() > $now) {
                    /** @var GroupSessionAttachment $gsa */
                    foreach ($group_session_attachments as $gsa) {
                        //if we can find a record in the GroupSessionAttachment table
                        //by matching attachment_id, attachment_type, session_id and publish_at is in the past
                        //check if the attachment belong to the group session of the user
                        if (
                            $gsa->getAttachmentType() == $a->getAttachmentType()
                            && $gsa->getAttachmentId() == $a->getId()
                            && $gsa->getPublishAt() <= $now
                        ) {
                            //ensure that the item belongs to the GROUP SESSION of the user; if it is publish the attachment
                            foreach ($group_session_ids as $gs_id) {
                                if ($gsa->getGroupSessionId() == $gs_id) {
                                    //override publish_at @ session with publish_at @ group
                                    $a->setPublishAt( $gsa->getPublishAt() );
                                    $a->setUpdated( $gsa->getUpdated() );
                                    break;
                                }
                            }
                        }
                    }
                }

                if( $a->getDocumentType() == 2 && $a->getPublishAt() <= $now ) {
                    if( is_null($this->getLatestHandout()) || $a->getUpdated() > $this->getLatestHandout()->getUpdated() ) {
                        $this->setLatestHandout($a);
                    }
                }

                // SHow Handouts irrespective of publish_at date
                if($this->showHandouts && $a->getDocumentType() == 2) {
                    array_push($attachments, $a);
                    continue;
                }

                // Check if publish_at date is in the future, if yes, then skip the item
                if($a->getPublishAt() > $now) {
                    continue;
                }

                //if none of the criteria above is skipped/process, just attach the file
                array_push($attachments, $a);
            }
        } else {
            $attachments = $allAttachments;
        }

        // serialise attachments
        if($this->serializeFully) $serialisedAttachments = $attachments;
        else {
            foreach($attachments as $a) {

                array_push($serialisedAttachments, ['id' => $a->getId(), 'type' => $a->getAttachmentType(), 'document_type' => $a->getDocumentType()]);
            }
        }
        return $serialisedAttachments;
    }

    public function getAllHandouts()
    {
        $handouts = [];
        $allAttachments = array_merge($this->videos->toArray(), $this->linkedDocuments->toArray(), $this->links->toArray(), $this->fileDocuments->toArray());

        /** @var Attachment $attachment */
        foreach($allAttachments as $attachment) {
            if($attachment->getDocumentType() == 2) {
                array_push($handouts, $attachment);
            }
        }

        return $handouts;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("latest_handout_delta")
     *
     * @param \DateTime $now reference date to determine if handouts are published
     *
     * @return null|\DateTime
     */
    public function getLatestHandoutPublishAt()
    {
        $latestHandout = $this->latestHandout;
        $latest = $this->getUpdated();

        if( !is_null($latestHandout) ) {
            if( $latestHandout->getUpdated() > $latest ) {
                $latest = $latestHandout->getUpdated();
            }
        }

        return $latest;
    }

    /**
     *  creates name for session folder programmatically based on session attributes
     *
     * @param boolean $shared flag to determine if the folder we need to get is shared
     *
     * @return String
     */
    public function getBoxFolderName( $shared = false )
    {
        // date should be in format YYYYMMDD
        $dateString = date_format( $this->getStartDate(), 'Ymd' );

        // folder format should be S-ABBR-COUNTRYCODE-YYYYMMDD
        $name = 'S-' . $this->getAbbreviation() . '-' . $this->getCourse()->getCountry() . '-' . $dateString;

        // append -S to end if shared folder
        if ( $shared ) $name = $name . '-S';

        return $name;
    }

    public function getFileDocuments()
    {
        $fileDocuments = [];
        if( $this->fileDocuments::class == \Doctrine\ORM\PersistentCollection::class
            || $this->fileDocuments::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $fileDocuments = $this->fileDocuments;
        }
        return $fileDocuments;

    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("group_sessions")
     *
     * @return array()
     */
    public function getGroupSessionIds()
    {
        $groupSessions = [];

        if($this->hideGroupSessions) {
            return $groupSessions;
        }

        if ($this->isWebView){
            return $this->findGroupSessionsForUser();
        } else {
            if ($this->serializePublishedSubEntities) {
                if ($this->group_sessions) {
                        if ($this->group_sessions->toArray() > 0) {
                            $group_sessions = $this->group_sessions->toArray();

                            /** @var GroupSession $group_session */
                            foreach ($group_sessions as $group_session) {
                             array_push($groupSessions, $group_session->getId());
                        }
                    }
                }
            }
        }

        return $groupSessions;
    }

    public function getGroupSessions()
    {
        $group_sessions = [];

        if( $this->group_sessions::class == \Doctrine\ORM\PersistentCollection::class
            || $this->group_sessions::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $group_sessions = $this->group_sessions;
        }

        return $group_sessions;
    }

    public function setWebView($isWebView){
        $this->isWebView = $isWebView;
    }

    public function findGroupSessionsForUser()
    {
        $items = [];

        if( $this->group_session_attachments_for != -1 ) {

            $group_sessions = $this->getGroupSessions();

            /** @var GroupSession $gs */
            foreach($group_sessions as $gs) {
                $groupMembers = $gs->getGroup()->getUsersList();

                if( in_array( $this->group_session_attachments_for, $groupMembers ) ) {
                    array_push( $items, $gs->getId() );
                }
            }
        }

        return $items;
    }

    public function getGroupSessionAttachments()
    {
        $attachments = [];

        if( $this->group_sessions_attachments::class == \Doctrine\ORM\PersistentCollection::class
            || $this->group_sessions_attachments::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $attachments = $this->group_sessions_attachments;
        }

        return $attachments;
    }

    public function getPendingAttachments()
    {
        $attachments = [];

        if( $this->pending_attachments::class == \Doctrine\ORM\PersistentCollection::class
            || $this->pending_attachments::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $attachments = $this->pending_attachments;
        }

        return $attachments;
    }

    /**
     * This function handles the cloning of Session
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
        $this->setBoxFolderId("S3-".mktime(date("H")));

        $abbrArray = explode("_", $this->getAbbreviation());
        if (count($abbrArray) > 1) array_shift($abbrArray);
        $newAbbr = implode('_', $abbrArray);
        $newAbbr = mktime(date("H"))."_".$newAbbr;
        $this->setAbbreviation($newAbbr);
    }
}
