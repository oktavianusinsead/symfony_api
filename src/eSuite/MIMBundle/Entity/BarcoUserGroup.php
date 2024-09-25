<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserGroup
 */
#[ORM\Table(name: 'barco_usergroup')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class BarcoUserGroup extends Base
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
    #[ORM\Column(name: 'group_id', type: 'text')]
    #[Assert\NotBlank(message: 'GroupId cannot be blank.')]
    private $groupId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'group_name', type: 'text')]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    private $groupName;

    /**
     * @var datetime
     */
    #[ORM\Column(name: 'group_date', type: 'datetime')]
    #[Assert\NotBlank(message: 'Daate cannot be blank.')]
    private $groupDateTime;

    /**
     * @var string
     */
    #[ORM\Column(name: 'group_campus', type: 'text')]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    private $groupCampus;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'group_term', type: 'integer')]
    private $groupTerm;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'group_class_nbr', type: 'integer')]
    private $groupClassNbr;

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
     * Set Group ID
     *
     * @param string $groupId
     * @return BarcoUserGroup
     */
    public function setGroupID($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get Group ID
     *
     * @return string
     */
    public function getGroupID()
    {
        return $this->groupId;
    }

    /**
     * Set Group name
     *
     * @param string $groupName
     * @return BarcoUserGroup
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * Get Group Name
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * Set Group date
     *
     * @param \DateTime $groupDate
     * @return BarcoUserGroup
     */
    public function setGroupDate($groupDate)
    {
        $this->groupDateTime = $groupDate;

        return $this;
    }

    /**
     * Get Group Date
     *
     * @return datetime
     */
    public function getGroupDate()
    {
        return $this->groupDateTime;
    }

    /**
     * Set Group Campus
     *
     * @param string $groupCampus
     * @return BarcoUserGroup
     */
    public function setGroupCampus($groupCampus)
    {
        $this->groupCampus = $groupCampus;

        return $this;
    }

    /**
     * Get Group Campus
     *
     * @return string
     */
    public function getGroupCampus()
    {
        return $this->groupCampus;
    }

    /**
     * Set Group Term
     *
     * @param integer $groupTerm
     * @return BarcoUserGroup
     */
    public function setGroupTerm($groupTerm)
    {
        $this->groupTerm = $groupTerm;

        return $this;
    }

    /**
     * Get Group Term
     *
     * @return integer
     */
    public function getGroupTerm()
    {
        return $this->groupTerm;
    }

    /**
     * Set Group Class Nbr
     *
     * @param integer $groupClassNbr
     * @return BarcoUserGroup
     */
    public function setGroupClassNbr($groupClassNbr)
    {
        $this->groupClassNbr = $groupClassNbr;

        return $this;
    }

    /**
     * Get Group Class Nbr
     *
     * @return integer
     */
    public function getGroupClassNbr()
    {
        return $this->groupClassNbr;
    }

}
