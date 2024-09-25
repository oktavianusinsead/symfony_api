<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * User profile cache
 */
#[ORM\Table(name: 'user_profiles_cache')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserProfileCache extends UserProfileAbstract
{
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\OneToOne(targetEntity: \User::class, inversedBy: 'userProfileCache')]
    private $user;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_updated', type: 'boolean', nullable: true, options: ['default' => false])]
    private $is_updated = false;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'last_esb_date_processed', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private $last_esb_date_processed;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'last_user_date_updated', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private $last_user_date_updated;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'is_new_work_exp', type: 'boolean', nullable: true, options: ['default' => false])]
    private $is_new_work_exp = false;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'updated_fields', type: 'text')]
    private $updatedFields;

    public function __construct()
    {
        parent::__construct();
        $current_date = new \DateTime();
        $this->last_esb_date_processed = $current_date;
        $this->last_user_date_updated = $current_date;
    }

    /**
     * Set User object
     *
     * @param User $user
     * @return UserProfileCache
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
     * @return UserProfileCache
     */
    public function setProfileUpdated(bool $is_updated)
    {
        $this->is_updated = $is_updated;
        return $this;
    }

    /**
     * @return bool
     */
    public function getProfileUpdated()
    {
        return $this->is_updated;
    }

    /**
     * @return UserProfileCache
     */
    public function setWorkExperienceStatus(bool $is_new_work_exp)
    {
        $this->is_new_work_exp = $is_new_work_exp;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWorkExperienceStatus()
    {
        return $this->is_new_work_exp;
    }

    /**
     * Set last_esb_date_processed
     *
     * @param string $date
     * @return Base
     */
    public function setLastESBDteProcessed($date)
    {
        $this->last_esb_date_processed = $date;

        return $this;
    }

    /**
     * Get last_esb_date_processed
     *
     * @return \DateTime
     */
    public function getLastESBDteProcessed()
    {
        return $this->last_esb_date_processed;
    }

    /**
     * Set last_user_date_updated
     *
     * @param string $date
     * @return Base
     */
    public function setLastUserDateUpdated($date)
    {
        $this->last_user_date_updated = $date;

        return $this;
    }

    /**
     * Get last_esb_date_processed
     *
     * @return \DateTime
     */
    public function getLastUserDateUpdated()
    {
        return $this->last_user_date_updated;
    }

    /**
     * Set updatedFields
     *
     * @param string $updatedFields
     * @return Base
     */
    public function setUpdatedFields($updatedFields)
    {
        $this->updatedFields = $updatedFields;

        return $this;
    }

    /**
     * Get updatedFields
     *
     * @return string
     */
    public function getUpdatedFields()
    {
        return $this->updatedFields;
    }

}
