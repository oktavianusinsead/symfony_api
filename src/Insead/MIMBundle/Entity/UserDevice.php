<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * UserDevice
 */
#[ORM\Table(name: 'user_devices')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserDevice extends Base
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
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'user_devices')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ios_device_id', type: 'string', length: 255)]
    private $ios_device_id;


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
     * @return UserDevice
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return integer
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set ios_device_id
     *
     * @param string $iosDeviceId
     * @return UserDevice
     */
    public function setIosDeviceId($iosDeviceId)
    {
        $this->ios_device_id = $iosDeviceId;

        return $this;
    }

    /**
     * Get ios_device_id
     *
     * @return string
     */
    public function getIosDeviceId()
    {
        return $this->ios_device_id;
    }


}
