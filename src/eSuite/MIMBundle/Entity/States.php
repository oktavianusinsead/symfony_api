<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use esuite\MIMBundle\Annotations\Validator as FormAssert;

/**
 * Country
 */
#[ORM\Table(name: 'states')]
#[ORM\UniqueConstraint(name: 'states_unique', columns: ['id', 'country_id', 'ps_state_code'])]
#[ORM\Entity]
class States extends Base
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
    #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Country::class, inversedBy: 'states')]
    private $country;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ps_state_code', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'PS state code cannot be blank.')]
    private $ps_state_code;


    /**
     * @var string
     */
    #[ORM\Column(name: 'title', type: 'text')]
    #[Assert\NotBlank(message: 'title cannot be blank.')]
    private $state_name;
    
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
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return States
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getStateCode()
    {
        return $this->ps_state_code;
    }

    /**
     * @param string $ps_state_code
     *
     * @return States
     */
    public function setStateCode($ps_state_code)
    {
        $this->ps_state_code = $ps_state_code;

        return $this;
    }

    /**
     * @return string
     */
    public function getStateName()
    {
        return $this->state_name;
    }

    /**
     * @param string $state_name
     *
     * @return States
     */
    public function setStateName($state_name)
    {
        $this->state_name = $state_name;

        return $this;
    }
}
