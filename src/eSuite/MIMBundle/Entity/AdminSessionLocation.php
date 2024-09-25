<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use esuite\MIMBundle\Annotations\Validator as FormAssert;


/**
 * Session
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: 'admin_session_location')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class AdminSessionLocation extends Base
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'adminSessionLocation')]
    private $course;

    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'adminSessionLocation')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'location', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Location cannot be blank.')]
    private $location;

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
     * @return AdminSessionLocation
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
     * Set user
     *
     * @param User $user
     * @return AdminSessionLocation
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
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("user_id")
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->getUser()->getId();
    }

    /**
     * Set location
     *
     * @param string $location
     * @return AdminSessionLocation
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }
}
