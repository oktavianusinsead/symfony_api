<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @Serializer\ExclusionPolicy("None")
 */
#[ORM\HasLifecycleCallbacks]
abstract class UserProfileAbstract extends Base
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
    #[ORM\Column(name: 'first_name', type: 'string', nullable: true)]
    protected $firstname;

    /**
     * @var string
     */
    #[ORM\Column(name: 'last_name', type: 'string', nullable: true)]
    protected $lastname;


    /**
     * @var string
     */
    #[ORM\Column(name: 'avatar', type: 'string', nullable: true)]
    protected $avatar;


    /**
     * @var string
     */
    #[ORM\Column(name: 'bio', type: 'string', nullable: true)]
    protected $bio;


    /**
     * @var string
     */
    #[ORM\Column(name: 'job_title', type: 'string', nullable: true)]
    protected $job_title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'organization_id', type: 'string', nullable: true)]
    protected $organization_id;


    /**
     * @var string
     */
    #[ORM\Column(name: 'organization_title', type: 'string', nullable: true)]
    protected $organization_title;


    /**
     * @var string
     */
    #[ORM\Column(name: 'upn_email', type: 'string', nullable: true)]
    protected $upn_email;


    /**
     * @var string
     */
    #[ORM\Column(name: 'nationality', type: 'string', nullable: true)]
    protected $nationality;


    /**
     * @var string
     */
    #[ORM\Column(name: 'constituent_types', type: 'string', nullable: true)]
    protected $constituent_types;


    /**
     * @var string
     */
    #[ORM\Column(name: 'cell_phone_prefix', type: 'string', nullable: true)]
    protected $cell_phone_prefix;


    /**
     * @var string
     */
    #[ORM\Column(name: 'cell_phone', type: 'string', nullable: true)]
    protected $cell_phone;


    /**
     * @var string
     */
    #[ORM\Column(name: 'personal_phone_prefix', type: 'string', nullable: true)]
    protected $personal_phone_prefix;

    /**
     * @var string
     */
    #[ORM\Column(name: 'personal_phone', type: 'string', nullable: true)]
    protected $personal_phone;


    /**
     * @var string
     */
    #[ORM\Column(name: 'work_phone_prefix', type: 'string', nullable: true)]
    protected $work_phone_prefix;


    /**
     * @var string
     */
    #[ORM\Column(name: 'work_phone', type: 'string', nullable: true)]
    protected $work_phone;


    /**
     * @var int
     */
    #[ORM\Column(name: 'preferred_phone', type: 'integer', nullable: true)]
    protected $preferred_phone;


    /**
     * @var string
     */
    #[ORM\Column(name: 'personal_email', type: 'string', nullable: true)]
    protected $personal_email;


    /**
     * @var string
     */
    #[ORM\Column(name: 'work_email', type: 'string', nullable: true)]
    protected $work_email;



    /**
     * @var int
     */
    #[ORM\Column(name: 'preferred_email', type: 'integer', nullable: true)]
    protected $preferred_email;


    /**
     * @var string
     */
    #[ORM\Column(name: 'address_line_1', type: 'string', nullable: true)]
    protected $address_line_1;

    /**
     * @var string
     */
    #[ORM\Column(name: 'address_line_2', type: 'string', nullable: true)]
    protected $address_line_2;

    /**
     * @var string
     */
    #[ORM\Column(name: 'address_line_3', type: 'string', nullable: true)]
    protected $address_line_3;


    /**
     * @var string
     */
    #[ORM\Column(name: 'state', type: 'string', nullable: true)]
    protected $state;


    /**
     * @var string
     */
    #[ORM\Column(name: 'postal_code', type: 'string', nullable: true)]
    protected $postal_code;


    /**
     * @var string
     */
    #[ORM\Column(name: 'country', type: 'string', nullable: true)]
    protected $country;

    /**
     * @var string
     */
    #[ORM\Column(name: 'country_code', type: 'string', nullable: true)]
    protected $country_code;

    /**
     * @var string
     */
    #[ORM\Column(name: 'city', type: 'string', nullable: true)]
    protected $city;

    /**
     * @var datetime
     */
    #[ORM\Column(name: 'job_start_date', type: 'datetime', nullable: true)]
    protected $job_start_date;

    /**
     * @var datetime
     */
    #[ORM\Column(name: 'job_end_date', type: 'datetime', nullable: true)]
    protected $job_end_date;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'currently_working_here', type: 'boolean', nullable: true, options: ['default' => true])]
    protected $currently_working_here;

    /**
     * @var string
     */
    #[ORM\Column(name: 'industry', type: 'string', nullable: true)]
    protected $industry;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @return $this
     */
    public function setFirstname(string $firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @return $this
     */
    public function setLastname(string $lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }


    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @return $this
     */
    public function setAvatar(string $avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * @return string
     */
    public function getBio()
    {
        return $this->bio;
    }

    /**
     * @return $this
     */
    public function setBio(string $bio)
    {
        $this->bio = $bio;
        return $this;
    }

    /**
     * @return string
     */
    public function getJobTitle()
    {
        return $this->job_title;
    }

    /**
     * @return $this
     */
    public function setJobTitle(string $job_title)
    {
        $this->job_title = $job_title;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrganizationId()
    {
        return $this->organization_id;
    }

    /**
     * @param string $organization_id
     * @return $this
     */
    public function setOrganizationId($organization_id)
    {
        $this->organization_id = $organization_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrganizationTitle()
    {
        return $this->organization_title;
    }

    /**
     * @param string $organization_title
     * @return $this
     */
    public function setOrganizationTitle($organization_title)
    {
        $this->organization_title = $organization_title;
        return $this;
    }

    /**
     * @return string
     */
    public function getUpnEmail()
    {
        return $this->upn_email;
    }

    /**
     * @return $this
     */
    public function setUpnEmail(string $upn_email)
    {
        $this->upn_email = $upn_email;
        return $this;
    }

    /**
     * @return string
     */
    public function getNationality()
    {
        return $this->nationality;
    }

    /**
     * @return $this
     */
    public function setNationality(string $nationality)
    {
        $this->nationality = $nationality;
        return $this;
    }

    /**
     * @return string
     */
    public function getConstituentTypes()
    {
        return $this->constituent_types;
    }

    /**
     * @return $this
     */
    public function setConstituentTypes(string $constituent_types)
    {
        $this->constituent_types = $constituent_types;
        return $this;
    }

    /**
     * @return string
     */
    public function getCellPhonePrefix()
    {
        return $this->cell_phone_prefix;
    }

    /**
     * @return $this
     */
    public function setCellPhonePrefix(string $cell_phone_prefix)
    {
        $this->cell_phone_prefix = $cell_phone_prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getCellPhone()
    {
        return $this->cell_phone;
    }

    /**
     * @return $this
     */
    public function setCellPhone(string $cell_phone)
    {
        $this->cell_phone = $cell_phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalPhonePrefix()
    {
        return $this->personal_phone_prefix;
    }

    /**
     * @return $this
     */
    public function setPersonalPhonePrefix(string $personal_phone_prefix)
    {
        $this->personal_phone_prefix = $personal_phone_prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalPhone()
    {
        return $this->personal_phone;
    }

    /**
     * @return $this
     */
    public function setPersonalPhone(string $personal_phone)
    {
        $this->personal_phone = $personal_phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkPhonePrefix()
    {
        return $this->work_phone_prefix;
    }

    /**
     * @return $this
     */
    public function setWorkPhonePrefix(string $work_phone_prefix)
    {
        $this->work_phone_prefix = $work_phone_prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkPhone()
    {
        return $this->work_phone;
    }

    /**
     * @return $this
     */
    public function setWorkPhone(string $work_phone)
    {
        $this->work_phone = $work_phone;
        return $this;
    }

    /**
     * @return int
     */
    public function getPreferredPhone()
    {
        return $this->preferred_phone;
    }

    /**
     * @return $this
     */
    public function setPreferredPhone(int $preferred_phone)
    {
        $this->preferred_phone = $preferred_phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalEmail()
    {
        return $this->personal_email;
    }

    /**
     * @return $this
     */
    public function setPersonalEmail(string $personal_email)
    {
        $this->personal_email = $personal_email;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkEmail()
    {
        return $this->work_email;
    }

    /**
     * @return $this
     */
    public function setWorkEmail(string $work_email)
    {
        $this->work_email = $work_email;
        return $this;
    }

    /**
     * @return int
     */
    public function getPreferredEmail()
    {
        return $this->preferred_email;
    }

    /**
     * @return $this
     */
    public function setPreferredEmail(int $preferred_email)
    {
        $this->preferred_email = $preferred_email;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressLine1()
    {
        return $this->address_line_1;
    }

    /**
     * @return $this
     */
    public function setAddressLine1(string $address_line_1)
    {
        $this->address_line_1 = $address_line_1;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressLine2()
    {
        return $this->address_line_2;
    }

    /**
     * @return $this
     */
    public function setAddressLine2(string $address_line_2)
    {
        $this->address_line_2 = $address_line_2;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressLine3()
    {
        return $this->address_line_3;
    }

    /**
     * @return $this
     */
    public function setAddressLine3(string $address_line_3)
    {
        $this->address_line_3 = $address_line_3;
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return $this
     */
    public function setState(string $state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * @return $this
     */
    public function setPostalCode(string $postal_code)
    {
        $this->postal_code = $postal_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return $this
     */
    public function setCountry(string $country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * @return $this
     */
    public function setCountryCode(string $country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return $this
     */
    public function setCity(string $city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return datetime
     */
    public function getJobStartDate()
    {
        return $this->job_start_date;
    }

    /**
     * @param $job_start_date
     * @return $this
     */
    public function setJobStartDate($job_start_date)
    {
        $this->job_start_date = $job_start_date;
        return $this;
    }

    /**
     * @return datetime
     */
    public function getJobEndDate()
    {
        return $this->job_end_date;
    }

    /**
     * @param $job_end_date
     * @return $this
     */
    public function setJobEndDate($job_end_date)
    {
        $this->job_end_date = $job_end_date;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCurrentlyWorkingHere()
    {
        return $this->currently_working_here;
    }

    /**
     * @param $currently_working_here
     * @return $this
     */
    public function setCurrentlyWorkingHere($currently_working_here)
    {
        $this->currently_working_here = $currently_working_here;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * @param $industry
     * @return $this
     */
    public function setIndustry($industry)
    {
        $this->industry = $industry;
        return $this;
    }
}
