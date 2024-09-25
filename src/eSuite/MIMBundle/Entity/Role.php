<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Role
 */
#[ORM\Table(name: 'roles')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Role extends Base
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \CourseSubscription::class, mappedBy: 'role')]
    protected $courseSubscription;

    public function __construct()
    {
        parent::__construct();
        $this->courseSubscription = new ArrayCollection();
    }

    /**
     * Get courseSubscription
     *
     * @return CourseSubscription
     */
    public function getCourseSubscription()
    {
        return $this->courseSubscription;
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
     * Set name
     *
     * @param string $name
     * @return Role
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
}
