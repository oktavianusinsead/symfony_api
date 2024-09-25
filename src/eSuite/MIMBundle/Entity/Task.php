<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\PersistentCollection;

/**
 * Task
 */
#[ORM\Table(name: 'tasks')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Task extends BaseTask
{
    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'tasks')]
    private $course;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \Subtask::class, mappedBy: 'task', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    private $subtasks;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'date', type: 'datetime', nullable: true)]
    private $date;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'published_at', type: 'datetime', nullable: true)]
    private $published_at;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean')]
    private $published = false;

    /**
     * @var boolean
     *
     * @Serializer\SerializedName("is_high_priority")
     */
    #[ORM\Column(name: 'isHighPriority', type: 'boolean', nullable: true)]
    private $highPriority = false;

    /**
     * @var boolean
     *
     * @Serializer\SerializedName("high_priority")
     */
    #[ORM\Column(name: 'marked_high_priority', type: 'boolean', nullable: true, options: ['default' => 0])]
    private $markedHighPriority = false;

    /**
     * @var boolean
     *
     * @Serializer\SerializedName("is_archived")
     */
    #[ORM\Column(name: 'is_archived', type: 'boolean', nullable: true)]
    private $archived = false;



    public function __construct()
    {
        parent::__construct();
        $this->setDefaults();
    }

    private function setDefaults() {
        $this->subtasks = new ArrayCollection();
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
     * @return Task
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
     * Set date
     *
     * @param string $date
     * @return Task
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Get list of SubTasks
     *
     * @return array()
     */
    public function getSubtasks()
    {
        $subtasks = [];

        if ($this->subtasks::class == \Doctrine\ORM\PersistentCollection::class
            || $this->subtasks::class == \Doctrine\Common\Collections\ArrayCollection::class
        ) {
            $subtasks = $this->subtasks->toArray();
        }

        if (count($subtasks)) {
            $maxIndx = -1;
            $reorderedSubtasks = [];

            /** @var Subtask $subtask */
            foreach ($subtasks as $subtask) {
                if (!is_null($subtask->getPosition()) && $subtask->getPosition() > $maxIndx) {
                    $maxIndx = $subtask->getPosition();
                }
            }

            //indexed
            for ($i = 0; $i <= $maxIndx; $i++) {
                /** @var Subtask $subtask */
                foreach ($subtasks as $subtask) {
                    if (!is_null($subtask->getPosition()) && $subtask->getPosition() == $i) {
                        array_push($reorderedSubtasks, $subtask);
                    }
                }
            }

            //non-indexed
            /** @var Subtask $subtask */
            foreach ($subtasks as $subtask) {
                if (is_null($subtask->getPosition())) {
                    array_push($reorderedSubtasks, $subtask);
                }
            }

            $subtasks = $reorderedSubtasks;
        }

        return $subtasks;
    }

    /**
     * Set subtasks
     *
     * @param PersistentCollection|ArrayCollection $subtasks
     * @return Task
     */
    public function setSubtasks($subtasks)
    {
        $this->subtasks = $subtasks;
        return $this;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("subtasks")
     *
     * @return array()
     */
    public function getSubtaskIds()
    {
        $subtasks = [];
        if ($this->getSubtasks()) {
            foreach ($this->getSubtasks() as $subtask) {
                if ($this->serializeFully) {
                    array_push($subtasks, $subtask);
                } else {
                    array_push($subtasks, $subtask->getId());
                }
            }
        }
        return $subtasks;
    }

    /**
     * Set published_at
     *
     * @param \DateTime $publishedAt
     * @return Task
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
     * @return Task
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
     * Set highPriority
     *
     * @param boolean $highPriority
     * @return Task
     */
    public function setHighPriority($highPriority)
    {
        $this->highPriority = $highPriority;

        return $this;
    }

    /**
     * Get highPriority
     *
     * @Serializer\SerializedName("high_priority")
     *
     * @return bool
     */
    public function getHighPriority()
    {
        return $this->highPriority;
    }

    /**
     * Set markedHighPriority
     *
     * @param boolean $markedHighPriority
     * @return Task
     */
    public function setMarkedHighPriority($markedHighPriority)
    {
        $this->markedHighPriority = $markedHighPriority;

        return $this;
    }

    /**
     * Get markedHighPriority
     *
     * @Serializer\SerializedName("high_priority")
     *
     * @return bool
     */
    public function getMarkedHighPriority()
    {
        return $this->markedHighPriority;
    }

    /**
     * Set archived
     *
     * @param boolean $archived
     * @return Task
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get course
     *
     * @Serializer\SerializedName("is_archived")
     *
     * @return bool
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     *  creates name for task folder
     */
    public function getBoxFolderName()
    {
        $name = 'T-' . $this->getId();

        return $name;
    }

    /**
     * This function handles the cloning of course and set the initial value
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
        $this->setPublishedAt($currentDateTime);
        $this->setArchived(false);
        $this->setBoxFolderId("S3-".mktime(date("H")));
    }
}
