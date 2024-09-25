<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * UserSubtasks
 *
 *
 */
#[ORM\Table(name: 'user_subtasks')]
#[ORM\Index(name: 'us_user_course_idx', columns: ['user_id', 'course_id'])]
#[ORM\UniqueConstraint(name: 'user_subtask_unique', columns: ['user_id', 'subtask_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserSubtask extends Base
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
     *
     * @var user
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'userSubtasks')]
    private $user;

    /**
     * @Serializer\Exclude
     *
     * @var subtask
     */
    #[ORM\JoinColumn(name: 'subtask_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Subtask::class, inversedBy: 'userSubtasks')]
    private $subtask;

    /**
     * @Serializer\Exclude
     *
     * @var course
     */
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'userSubtasks')]
    private $course;


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
     * Set user
     *
     * @param User $user
     * @return UserSubtask
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set Subtask
     *
     * @param Subtask $subtask
     * @return UserSubTask
     */
    public function setSubtask($subtask)
    {
        $this->subtask = $subtask;

        return $this;
    }

    /**
     * Get Subtask
     *
     * @return Subtask
     */
    public function getSubtask()
    {
        return $this->subtask;
    }

    /**
     * Set Course
     *
     * @param Course $course
     * @return UserSubtask
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get Course
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }
}
