<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * User
 */
#[ORM\Table(name: 'administrators')]
#[ORM\Index(name: 'administrator_idx', columns: ['peoplesoft_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('peoplesoft_id')]
class Administrator extends Base
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
    #[ORM\Column(name: 'peoplesoft_id', type: 'string', length: 255, unique: true)]
    public $peoplesoft_id;

    /**
     * @var \DateTime
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private $lastLogin;

    /**
     * @var boolean
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'is_blocked', type: 'boolean')]
    private $blocked = TRUE;

    /**
     * @var boolean
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'email_sent', type: 'boolean')]
    private $emailSent = FALSE;

    /**
     * @var boolean
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'is_faculty', type: 'boolean')]
    private $faculty = FALSE;

    /**
     * @var boolean
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'is_support_only', type: 'boolean')]
    private $supportOnly = FALSE;


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
     * Set Last Login of Administrator
     *
     * @param string $lastLogin
     * @return Administrator
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get Last Login of Administrator
     * @Serializer\VirtualProperty
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set Blocked
     *
     * @param boolean $blocked
     * @return Administrator
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;

        return $this;
    }

    /**
     * Get Blocked
     *
     * @return boolean
     */
    public function getBlocked()
    {
        return $this->blocked;
    }

    /**
     * Set Email Sent
     *
     * @param boolean $emailSent
     * @return Administrator
     */
    public function setEmailSent($emailSent)
    {
        $this->emailSent = $emailSent;

        return $this;
    }

    /**
     * Get Email Sent
     *
     * @return boolean
     */
    public function getEmailSent()
    {
        return $this->emailSent;
    }

    /**
     * Set Faculty Flag
     *
     * @param boolean $faculty
     * @return Administrator
     */
    public function setFaculty($faculty)
    {
        $this->faculty = $faculty;

        return $this;
    }

    /**
     * Get Faculty Flag
     *
     * @return boolean
     */
    public function getFaculty()
    {
        return $this->faculty;
    }


    /**
     * Set Support Only Flag
     *
     * @param boolean $supportOnly
     * @return Administrator
     */
    public function setSupportOnly($supportOnly)
    {
        $this->supportOnly = $supportOnly;

        return $this;
    }

    /**
     * Get Support Only Flag
     *
     * @return boolean
     */
    public function getSupportOnly()
    {
        return $this->supportOnly;
    }
}
