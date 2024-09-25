<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * User
 */
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'user_idx', columns: ['peoplesoft_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('peoplesoft_id')]
class User extends Base
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
    #[ORM\Column(name: 'box_id', type: 'string', nullable: true)]
    private $boxId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'box_email', type: 'string', nullable: true)]
    private $boxEmail;

    /**
     * @var string
     */
    #[ORM\Column(name: 'peoplesoft_id', type: 'string', length: 255, unique: true)]
    public $peoplesoft_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'vanilla_user_id', type: 'integer', nullable: true, unique: true)]
    private $vanillaUserId;

    /**
     * @var string
     *
     */
    protected $ios_device_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'first_name', type: 'string', nullable: true)]
    private $firstname;

    /**
     * @var string
     */
    #[ORM\Column(name: 'last_name', type: 'string', nullable: true)]
    private $lastname;

    /**
     * @var \DateTime
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'profile_last_updated', type: 'datetime', nullable: true)]
    private $profileLastUpdated;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \CourseSubscription::class, mappedBy: 'user')]
    protected $courseSubscription;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \ProgrammeAdministrator::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $programmeAdmins;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserDevice::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    private $user_devices;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserToken::class, mappedBy: 'user', orphanRemoval: true, cascade: ['all'])]
    private $user_tokens;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserDocument::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserSubtask::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userSubtasks;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserFavourite::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userFavourites;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserAnnouncement::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userAnnouncements;

    /**
     * @Serializer\Exclude
     */
    #[ORM\ManyToMany(targetEntity: \Group::class, mappedBy: 'group_members')]
    protected $userGroups;

    /**
     * @Serializer\Exclude
     */
    #[ORM\ManyToMany(targetEntity: \Session::class, mappedBy: 'professors')]
    protected $userSessions;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \CourseBackupEmail::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userCourseBackupEmail;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \ProgrammeUser::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    private $coreUserGroup;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \AdminSessionLocation::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $adminSessionLocation;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'agreement', type: 'boolean', options: ['default' => 0], nullable: false)]
    public $agreement = false;

    /**
     * @var \DateTime
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'agreement_at', type: 'datetime', nullable: true)]
    protected $agreementDate;

    /**
     * @var boolean
     *
     * @Serializer\Exclude
     **/
    #[ORM\Column(name: 'vanilla_user_refresh', type: 'boolean', options: ['default' => 0], nullable: false)]
    private $vanillaUserRefresh = false;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'last_login_at', type: 'datetime', nullable: true)]
    protected $last_login_at;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: \VanillaUserGroup::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $vanillaUserGroup;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \VanillaConversation::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userVanillaConversation;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToOne(targetEntity: \UserProfileCache::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userProfileCache;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToOne(targetEntity: \UserProfile::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userProfile;



    protected $memberships;

    /**
     * @return array
     */
    public function getMemberships()
    {
        return $this->memberships;
    }

    public function setMemberships(mixed $memberships): void
    {
        $this->memberships = $memberships;
    }

    public function setName(): void
    {
        if ($this->getCacheProfile()){
            /** @var UserProfileCache $cacheProfile */
            $cacheProfile = $this->getCacheProfile();
            if ($cacheProfile->getFirstname()){
                $this->firstname = $cacheProfile->getFirstname();
            }

            if ($cacheProfile->getLastname()){
                $this->lastname = $cacheProfile->getLastname();
            }
        }

    }
    
    public function __construct()
    {
        parent::__construct();
        $this->user_devices = new ArrayCollection();
        $this->user_tokens  = new ArrayCollection();
        $this->userGroups   = new ArrayCollection();
        $this->userSessions = new ArrayCollection();
        $this->courseSubscription = new ArrayCollection();
        $this->programmeAdmins = new ArrayCollection();
        $this->userDocuments = new ArrayCollection();
        $this->userSubtasks = new ArrayCollection();
        $this->userFavourites = new ArrayCollection();
        $this->userAnnouncements = new ArrayCollection();
        $this->userCourseBackupEmail = new ArrayCollection();
        $this->coreUserGroup = new ArrayCollection();
        $this->adminSessionLocation = new ArrayCollection();
        $this->vanillaUserGroup = new ArrayCollection();
        $this->userVanillaConversation = new ArrayCollection();
    }

    /**
     * Get programmeAdmins
     *
     * @return ProgrammeAdministrator
     */
    public function getProgrammeAdministrators()
    {
        return $this->programmeAdmins;
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
     * Get Programme Core Group
     *
     * @return ProgrammeUser
     */
    public function getProgrammeCoreGroup()
    {
        return $this->coreUserGroup;
    }

    /**
     * Get userAnnouncements
     *
     * @return UserAnnouncement
     */
    public function getUserAnnouncements()
    {
        return $this->userAnnouncements;
    }

    /**
     * Get userDocuments
     *
     * @return UserDocument
     */
    public function getUserDocuments()
    {
        return $this->userDocuments;
    }

    /**
     * Get userFavourites
     *
     * @return UserFavourite
     */
    public function getUserFavourites()
    {
        return $this->userFavourites;
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set boxId
     *
     * @param integer $boxId
     * @return User
     */
    public function setBoxId($boxId)
    {
        $this->boxId = $boxId;

        return $this;
    }

    /**
     * Get boxId
     *
     * @return integer
     */
    public function getBoxId()
    {
        return $this->boxId;
    }

    /**
     * Set boxEmail
     *
     * @param string $boxEmail
     * @return User
     */
    public function setBoxEmail($boxEmail)
    {
        $this->boxEmail = $boxEmail;

        return $this;
    }

    /**
     * Get boxEmail
     *
     * @return string
     */
    public function getBoxEmail()
    {
        return $this->boxEmail;
    }

    /**
     * Set ios_device_id
     *
     * @param string $ios_device_id
     * @return User
     */
    public function setIosDeviceId($ios_device_id)
    {
        if($ios_device_id) {
            foreach($this->getUserDevices() as $user_device) {
                if($user_device->getIosDeviceId() == $ios_device_id) {
                    //If Device Id is already associated with user, do not do anything
                    return $this;
                }
            }
            // Add device to user
            $userDevice = new UserDevice();
            $userDevice->setUser($this);
            $userDevice->setIosDeviceId($ios_device_id);

            $this->addUserDevice($userDevice);
        }

        return $this;
    }

    public function unsetIosDeviceId($ios_device_id)
    {
        if($ios_device_id) {
            foreach($this->getUserDevices() as $user_device) {
                if($user_device->getIosDeviceId() == $ios_device_id) {
                    $this->removeUserDevice($user_device);
                    break;
                }
            }
        }

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

    /**
     * Add a UserDevice to this User
     *
     *
     * @return User
     */
    public function addUserDevice(UserDevice $user_device)
    {
        if(!$this->user_devices->contains($user_device)) {
            $this->user_devices->add($user_device);
        }
        return $this;
    }

    /**
     * Remove a UserDevice from this User
     *
     * @param User|\Insead\MIMBundle\Entity\UserDevice $user_device
     *
     * @return User
     */
    public function removeUserDevice(UserDevice $user_device)
    {
        if($this->user_devices->contains($user_device)) {
            $this->user_devices->removeElement($user_device);
        }
        return $this;
    }

    /**
     * Get list of UserDevices assigned to this User
     *
     * @return array()
     */
    public function getUserDevices()
    {
        return $this->user_devices->toArray();
    }

    /**
     * Get list of UserToken s assigned to this User
     *
     * @return array()
     */
    public function getUserTokens()
    {
        return $this->user_tokens->toArray();
    }

    public function setUserToken($user_token)
    {
        if($user_token) {
            $this->user_tokens->add($user_token);
        }
        return $this;
    }

    /**
     * Remove a UserToken for this User
     *
     * @param String $access_token
     *
     * @return User
     */
    public function removeUserOauthAccessToken($access_token)
    {
        if($access_token) {
            /** @var UserToken $user_token */
            foreach($this->getUserTokens() as $user_token) {
                if($user_token->getOauthAccessToken() == $access_token) {
                    $this->user_tokens->removeElement($user_token);
                    break;
                }
            }
        }
        return $this;
    }

    public function resetUserTokens()
    {
        foreach($this->getUserTokens() as $user_token) {
            $this->user_tokens->removeElement($user_token);
        }
        return $this;
    }


    /**
     * Set Accepted Terms and Conditions status
     *
     * @param bool $agreement
     * @return User
     */
    public function setAgreement($agreement)
    {
        $this->agreement = $agreement;

        return $this;
    }


    /**
     * Get Accepted Terms and Conditions status
     *
     * @return bool
     */
    public function getAgreement()
    {
        return $this->agreement;
    }

    public function getUserGroups()
    {
        return $this->userGroups;
    }

    public function getUserSessions()
    {
        return $this->userSessions;
    }

    /**
     * Set Agreement Date
     *
     * @param string $agreementDate
     * @return User
     */
    public function setAgreementDate($agreementDate)
    {
        $this->agreementDate = $agreementDate;

        return $this;
    }

    /**
     * Get Agreement Date
     * @Serializer\VirtualProperty
     *
     * @return \DateTime
     */
    public function getAgreementDate()
    {
        return $this->agreementDate;
    }

    /**
     * Set Last Login Date
     *
     * @param string $last_login_at
     * @return User
     */
    public function setLastLoginDate($last_login_at)
    {
        $this->last_login_at = $last_login_at;

        return $this;
    }

    /**
     * Get Last Login Date
     * @Serializer\VirtualProperty
     *
     * @return \DateTime
     */
    public function getLastLoginDate()
    {
        return $this->last_login_at;
    }

    /**
     * Set Profile Last Updated
     *
     * @param string $profileLastUpdated
     * @return User
     */
    public function setProfileLastUpdated($profileLastUpdated)
    {
        $this->profileLastUpdated = $profileLastUpdated;

        return $this;
    }

    /**
     * Get Profile Last Updated
     * @Serializer\VirtualProperty
     *
     * @return \DateTime
     */
    public function getProfileLastUpdated()
    {
        return $this->profileLastUpdated;
    }

    /**
     * Set VanillaUserId
     *
     * @param string $vanillaUserId
     * @return User
     */
    public function setVanillaUserId($vanillaUserId)
    {
        $this->vanillaUserId = $vanillaUserId;

        return $this;
    }

    /**
     * Get VanillaUserId
     *
     * @return string
     */
    public function getVanillaUserId()
    {
        return $this->vanillaUserId;
    }


    /**
     * Set VanillaUserRefresh
     *
     * @param string $vanillaUserRefresh
     * @return User
     */
    public function setVanillaUserRefresh($vanillaUserRefresh)
    {
        $this->vanillaUserRefresh = $vanillaUserRefresh;

        return $this;
    }

    /**
     * Get VanillaUserRefresh
     *
     * @return string
     */
    public function getVanillaUserRefresh()
    {
        return $this->vanillaUserRefresh;
    }

    /**
     * Set Firstname
     *
     * @param string $firstname
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get Firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        if ($this->getCacheProfile()){
            /** @var UserProfileCache $cacheProfile */
            $cacheProfile = $this->getCacheProfile();
            if ($cacheProfile->getFirstname()){
                return $cacheProfile->getFirstname();
            }
        }

        return $this->firstname;
    }

    /**
     * Set Lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get Firstname
     *
     * @return string
     */
    public function getLastname()
    {
        if ($this->getCacheProfile()){
            /** @var UserProfileCache $cacheProfile */
            $cacheProfile = $this->getCacheProfile();
            if ($cacheProfile->getLastname()){
                return $cacheProfile->getLastname();
            }
        }

        return $this->lastname;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Exclude
     *
     * @return array
     */
    public function getPSoftConstituentType(){
        return [
            1   => 'Alumni',
            6   => 'Student',
            7   => 'Faculty',
            9   => 'Staff',
            12  => 'Affiliate',
            90  => 'INSEAD Coaches',
            93  => 'Participant',
            94  => 'Past Participant',
            98  => 'INSEAD Contractor',
            99  => 'INSEAD Client',
            100 => 'Exchanger Student'
        ];
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Exclude
     *
     * @return array
     */
    public function getUserConstituentTypeString(){
        $stringUsersConstituentType = [];
        $usersConstituentType = false;

        if ($this->getCoreProfile()) {
            $usersConstituentType = $this->getCoreProfile()->getConstituentTypes();
        } elseif ($this->getCacheProfile()) {
            $usersConstituentType = $this->getCacheProfile()->getConstituentTypes();
        }

        if ($usersConstituentType){
            $constituentTypeArray = array_map('intval', array_map('trim', explode(",", (string) $usersConstituentType)));
            foreach($constituentTypeArray as $constituentID){
                array_push($stringUsersConstituentType, ['constituent_type' => $this->getPSoftConstituentType()[$constituentID]]);
            }
        }

        return $stringUsersConstituentType;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Exclude
     *
     * @return array
     */
    public function getCoreProfile(){
        return $this->userProfile;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Exclude
     *
     * @return array
     */
    public function getCacheProfile(){
        return $this->userProfileCache;
    }

    /**
     * Constituent types not allowed to update
     *
     * 7 = Faculty
     * 9 = Staff
     * 90 = INSEAD Coaches
     * 98 = INSEAD Contractor
     *
     */
    private function getNotAllowedConstituentTypeToUpdate(){
        return [7,9];
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("isAllowedToUpdate")
     *
     * @return bool
     */
    public function getAllowedToUpdate()
    {
        if ($this->userProfileCache && $this->userProfileCache->getConstituentTypes()) {
            $constituent_types = ($this->userProfileCache->getConstituentTypes() ? array_map('intval', array_map('trim', explode(",", (string) $this->userProfileCache->getConstituentTypes()))) : NULL);
            return 0 == count(array_intersect($this->getNotAllowedConstituentTypeToUpdate(), $constituent_types));
        } else {
            return false;
        }
    }

    public function getUserProfileCore(){
        return $this->userProfile;
    }

    public function getUserProfileCache(){
        return $this->userProfileCache;
    }
}
