<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * UserAnnouncements
 */
#[ORM\Table(name: 'user_announcements')]
#[ORM\Index(name: 'course_user_idx', columns: ['user_id', 'course_id'])]
#[ORM\UniqueConstraint(name: 'user_announcement_unique', columns: ['user_id', 'announcement_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserAnnouncement extends Base
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
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'userAnnouncements')]
    private $user;

    /**
     * @Serializer\Exclude
     *
     * @var announcement
     */
    #[ORM\JoinColumn(name: 'announcement_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Announcement::class, inversedBy: 'userAnnouncements')]
    private $announcement;

    /**
     * @Serializer\Exclude
     *
     * @var course
     */
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'userAnnouncements')]
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
     * @return UserAnnouncement
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
     * Set Announcement
     *
     * @param Announcement $announcement
     * @return UserAnnouncement
     */
    public function setAnnouncement($announcement)
    {
        $this->announcement = $announcement;

        return $this;
    }

    /**
     * Get Announcement
     *
     * @return Announcement
     */
    public function getAnnouncement()
    {
        return $this->announcement;
    }

    /**
     * Set Course
     *
     * @param Course $course
     *
     * @return UserAnnouncement
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
