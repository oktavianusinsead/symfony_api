<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SubTask
 */
#[ORM\Table(name: 'subtasks')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Subtask extends BaseSubtask
{
    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Task::class, inversedBy: 'subtasks')]
    private $task;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'is_upload_to_s3', type: 'integer', nullable: true)]
    private $is_upload_to_s3;

    /**
     * @var string
     *
     * @Serializer\Exclude
     */
    #[ORM\Column(name: 'aws_path', type: 'text')]
    private $aws_path;

    /**
     * @var string
     */
    #[ORM\Column(name: 'file_id', type: 'string', length: 255)]
    private $file_id;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserSubtask::class, mappedBy: 'subtask', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userSubtasks;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults();
    }

    private function setDefaults() {
        $this->userSubtasks = new ArrayCollection();
    }

    /**
     * Get userSubtasks
     *
     * @return UserSubtask
     */
    public function getUserSubtasks()
    {
        return $this->userSubtasks;
    }

    /**
     * Set task
     *
     * @param Task $task
     * @return SubTask
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get task
     *
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("task_id")
     *
     * @return string
     */
    public function getTaskId()
    {
        if($this->task) {
           return $this->getTask()->getId();
        } else {
            return '';
        }
    }

    /**
     * Set is_upload_to_s3
     *
     * @param $is_upload_to_s3
     * @return Subtask
     */
    public function setUploadToS3($is_upload_to_s3)
    {
        $this->is_upload_to_s3 = $is_upload_to_s3;

        return $this;
    }

    /**
     * Get is_upload_to_s3
     *
     * @return string
     */
    public function getUploadToS3()
    {
        return $this->is_upload_to_s3;
    }

    /**
     * Set aws_path
     *
     * @param string $aws_path
     * @return Subtask
     */
    public function setAwsPath($aws_path)
    {
        $this->aws_path = $aws_path;

        return $this;
    }

    /**
     * Get aws_path
     *
     * @return string
     */
    public function getAwsPath()
    {
        return $this->aws_path;
    }

    /**
     * Set file_id
     *
     * @param string $fileId
     * @return FileDocument
     */
    public function setFileId($fileId)
    {
        $this->file_id = $fileId;

        return $this;
    }

    /**
     * Get file_id
     *
     * @return string|null
     */
    public function getFileId(): string|null
    {
        return $this->file_id;
    }


    /**
     * This function handles the cloning of Subtask
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

        $this->setAwsPath(null);
        $this->setFileId(null);
        $this->setBoxId(null);
    }
}
