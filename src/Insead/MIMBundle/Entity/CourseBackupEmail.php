<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CourseBackupEmail
 */
#[ORM\Table(name: 'course_backup_emails')]
#[ORM\UniqueConstraint(name: 'course_backup_email_unique', columns: ['course_id', 'user_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CourseBackupEmail extends Base
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
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'courseBackupEmail')]
    #[Assert\NotBlank(message: 'Course cannot be blank.')]
    private $course;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'userCourseBackupEmail')]
    #[Assert\NotBlank(message: 'User cannot be blank.')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'user_email', type: 'string', length: 255, nullable: false)]
    private $userEmail;


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
     * @return CourseBackupEmail
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
     * Set user
     *
     * @param User $user
     * @return CourseBackupEmail
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
     * Set userEmail
     *
     * @param String $userEmail
     * @return CourseBackupEmail
     */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    /**
     * Get userEmail
     *
     * @return String
     */
    public function getUserEmail()
    {
        return $this->userEmail;
    }
}
