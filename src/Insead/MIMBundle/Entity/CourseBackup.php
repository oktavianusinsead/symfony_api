<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * CourseBackup
 */
#[ORM\Table(name: 'course_backups')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CourseBackup extends Base
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
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false, unique: true)]
    #[ORM\OneToOne(targetEntity: \Course::class, inversedBy: 'courseBackup')]
    private $course;

    /**
     * @var string
     */
    #[ORM\Column(name: 's3_path', type: 'text', nullable: true)]
    private $s3_path;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'size', type: 'integer', nullable: true)]
    private $size;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'completed', type: 'datetime', nullable: true)]
    private $completed;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'in_progress', type: 'boolean')]
    private $in_progress = true;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'start_at', type: 'datetime', nullable: true)]
    private $start_at;


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
     * Set course
     *
     * @param Course $course
     * @return CourseBackup
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
        if($this->course) {
            return $this->getCourse()->getId();
        } else {
            return '';
        }
    }

    /**
     * Set s3_path
     *
     * @param string $s3Path
     * @return CourseBackup
     */
    public function setS3Path($s3Path)
    {
        $this->s3_path = $s3Path;

        return $this;
    }

    /**
     * Get s3_path
     *
     * @return string
     */
    public function getS3Path()
    {
        return $this->s3_path;
    }

    /**
     * Set size
     *
     * @param integer $size
     * @return CourseBackup
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set completed
     *
     * @param \DateTime $completed
     * @return CourseBackup
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * Get completed
     *
     * @return \DateTime
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * Set in_progress
     *
     * @param boolean $in_progress
     * @return CourseBackup
     */
    public function setInProgress($in_progress)
    {
        $this->in_progress = $in_progress;

        return $this;
    }

    /**
     * Get in_progress
     *
     * @return boolean
     */
    public function getInProgress()
    {
        return $this->in_progress;
    }

    /**
     * Set start_at
     *
     * @param \DateTime $start_at
     * @return CourseBackup
     */
    public function setStart($start_at)
    {
        $this->start_at = $start_at;

        return $this;
    }

    /**
     * Get start_at
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start_at;
    }
}
