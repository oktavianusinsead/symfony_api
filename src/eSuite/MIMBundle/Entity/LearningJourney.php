<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * LearningJourney
 */
#[ORM\Table(name: 'learning_journey')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class LearningJourney extends Base
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
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'learning_journey')]
    private $course_id;

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

    public function __construct() {
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
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("course_id")
     *
     * @return int
     */
    public function getCourseId()
    {
        return $this->getCourse()->getId();
    }

    /**
     * Set course
     *
     * @param Course $course_id
     * @return LearningJourney
     */
    public function setCourse($course_id)
    {
        $this->course_id = $course_id;

        return $this;
    }

    /**
     * Get course
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course_id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return LearningJourney
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
     * @return LearningJourney
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
     * @return LearningJourney
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
     * @return LearningJourney
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
