<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

/**
 * Country
 */
#[ORM\Table(name: 'countries')]
#[ORM\UniqueConstraint(name: 'countries_unique', columns: ['id', 'ps_country_code'])]
#[ORM\Entity]
class Country extends Base
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
    #[ORM\Column(name: 'ps_country_code', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'PS country code cannot be blank.')]
    private $ps_country_code;


    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'text')]
    #[Assert\NotBlank(message: 'title cannot be blank.')]
    private $title;


    /**
     * @var string
     */
    #[ORM\Column(name: 'nationality', type: 'text')]
    #[Assert\NotBlank(message: 'nationality cannot be blank.')]
    private $nationality;



    /**
     * @var string
     */
    #[ORM\Column(name: 'phone_code', type: 'text')]
    #[Assert\NotBlank(message: 'phone_code cannot be blank.')]
    private $phone_code;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \States::class, mappedBy: 'country', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $states;


    public function __construct()
    {
        parent::__construct();
        $this->states = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPsCountryCode(): string
    {
        return $this->ps_country_code;
    }

    public function setPsCountryCode(string $ps_country_code): void
    {
        $this->ps_country_code = $ps_country_code;
    }

    /**
     * @return string
     *
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getNationality(): string
    {
        return $this->nationality;
    }

    public function setNationality(string $nationality): void
    {
        $this->nationality = $nationality;
    }

    /**
     * @return string
     */
    public function getPhoneCode(): string
    {
        return $this->phone_code;
    }

    public function setPhoneCode(string $phone_code):void
    {
        $this->phone_code = $phone_code;
    }

    /**
     * @return mixed
     */
    public function getStates()
    {
        return $this->states;
    }

}
