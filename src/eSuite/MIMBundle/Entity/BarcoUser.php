<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 */
#[ORM\Table(name: 'barco_user')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class BarcoUser extends Base
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
    #[ORM\Column(name: 'peoplesoft_id', type: 'string', length: 255)]
    public $peoplesoft_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'barco_user_id', type: 'text')]
    #[Assert\NotBlank(message: 'Barco user id cannot be blank.')]
    private $barcoUserId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'firstname', type: 'text')]
    #[Assert\NotBlank(message: 'Lastname cannot be blank.')]
    private $firstName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'lastname', type: 'text')]
    #[Assert\NotBlank(message: 'Firstname cannot be blank.')]
    private $lastName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'displayname', type: 'text')]
    private $displayName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'upn', type: 'text')]
    #[Assert\NotBlank(message: 'UPN cannot be blank.')]
    private $upn;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Barco User ID
     *
     * @param string $barco_user_id
     * @return BarcoUser
     */
    public function setBarcoUserId($barco_user_id)
    {
        $this->barcoUserId = $barco_user_id;

        return $this;
    }

    /**
     * Get Barco User ID
     *
     * @return string
     */
    public function getBarcoUserId()
    {
        return $this->barcoUserId;
    }

    /**
     * Set First Name
     *
     * @param string $firstName
     * @return BarcoUser
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get First Name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set Last Name
     *
     * @param string $lastName
     * @return BarcoUser
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get Last Name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set Last Name
     *
     * @param string $displayName
     * @return BarcoUser
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Get Display Name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Set UPN / esuite Login
     *
     * @param string $upn
     * @return BarcoUser
     */
    public function setesuiteLogin($upn)
    {
        $this->upn = $upn;

        return $this;
    }

    /**
     * Get UPN / esuite Login
     *
     * @return string
     */
    public function getesuiteLogin()
    {
        return $this->upn;
    }
}
