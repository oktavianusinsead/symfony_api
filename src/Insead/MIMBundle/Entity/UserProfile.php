<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * User Profiles
 */
#[ORM\Table(name: 'user_profiles')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserProfile extends UserProfileAbstract
{
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\OneToOne(targetEntity: \User::class, inversedBy: 'userProfile')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'preferred_job_title', type: 'string', nullable: true)]
    private $preferred_job_title;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'hide_phone', type: 'boolean', options: ['default' => true])]
    private $hide_phone = true;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'hide_email', type: 'boolean', options: ['default' => true])]
    private $hide_email = true;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'has_access', type: 'boolean', nullable: true, options: ['default' => true])]
    private $has_access = true;

    /**
     * Set User object
     *
     * @param User $user
     * @return UserProfile
     */
    public function setUser($user){
        $this->user = $user;

        return $this;
    }

    /**
     * Retrieve user object
     *
     * @return User
     */
    public function getUser(){
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPreferredJobTitle()
    {
        return $this->preferred_job_title;
    }

    /**
     * @return UserProfile
     */
    public function setPreferredJobTitle(string $preferred_job_title)
    {
        $this->preferred_job_title = $preferred_job_title;
        return $this;
    }

    /**
     * @return string
     */
    public function getHidePhone()
    {
        return $this->hide_phone;
    }

    /**
     * @return UserProfile
     */
    public function setHidePhone(string $hide_phone)
    {
        $this->hide_phone = $hide_phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getHideEmail()
    {
        return $this->hide_email;
    }

    /**
     * @return UserProfile
     */
    public function setHideEmail(string $hide_email)
    {
        $this->hide_email = $hide_email;
        return $this;
    }

    /**
     * @return bool
     *
     */
    public function getHasAccess()
    {
        return $this->has_access;

    }

    /**
     * @return UserProfile
     */
    public function setHasAccess(bool $has_access)
    {
        $this->has_access = $has_access;
        return $this;
    }

}
