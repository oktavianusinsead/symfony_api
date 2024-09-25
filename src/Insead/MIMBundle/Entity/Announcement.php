<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Announcement
 */
#[ORM\Table(name: 'announcements')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Announcement extends Base
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
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'announcements')]
    private $course;

    /**
     * @var string
     */
    #[ORM\Column(name: 'peoplesoft_id', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Peoplesoft Id cannot be blank.')]
    public $peoplesoft_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Title cannot be blank.')]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text')]
    #[Assert\NotBlank(message: 'Content cannot be blank.')]
    private $description;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'published_at', type: 'datetime', nullable: true)]
    private $published_at;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean')]
    private $published = FALSE;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserAnnouncement::class, mappedBy: 'announcement', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userAnnouncements;

    public function __construct() {
        parent::__construct();
        $this->userAnnouncements = new ArrayCollection();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("author")
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->getPeoplesoftId();
    }

    /**
     * Get userAnnouncements
     *
     * @return ArrayCollection
     */
    public function getUserAnnouncements()
    {
        return $this->userAnnouncements;
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
     * Set course
     *
     * @param Course $course
     * @return Announcement
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
     * Set title
     *
     * @param string $title
     * @return Announcement
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
     * @return Announcement
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
     * Set published_at
     *
     * @param \DateTime $publishedAt
     * @return Announcement
     */
    public function setPublishedAt($publishedAt)
    {
        $this->published_at = $publishedAt;

        return $this;
    }

    /**
     * Get published_at
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->published_at;
    }

    /**
     * Set published
     *
     * @param boolean $published
     * @return Announcement
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
}
