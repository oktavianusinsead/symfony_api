<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

/**
 * GroupSessionAttachment
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: 'programmes_users')]
#[ORM\UniqueConstraint(name: 'programme_users_unique', columns: ['programme_id', 'user_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ProgrammeUser extends Base
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
    #[ORM\JoinColumn(name: 'programme_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Programme::class, inversedBy: 'coreUserGroup', fetch: 'LAZY')]
    protected $programme;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'coreUserGroup', fetch: 'LAZY')]
    protected $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'row_index', type: 'integer')]
    #[Assert\NotBlank(message: 'Row Index cannot be blank.')]
    private $row_index;

    /**
     * @var string
     */
    #[ORM\Column(name: 'order_index', type: 'integer')]
    #[Assert\NotBlank(message: 'Order Index cannot be blank.')]
    private $order_index;

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
     * Set Programme
     *
     * @param Programme $programme
     * @return ProgrammeUser
     */
    public function setProgramme($programme)
    {
        $this->programme = $programme;

        return $this;
    }

    /**
     * Get Programme
     *
     * @return Programme
     */
    public function getProgramme()
    {
        return $this->programme;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("programme_id")
     *
     * @return string
     */
    public function getProgrammeId()
    {
        return $this->getProgramme()->getId();
    }

    /**
     * Set User
     *
     * @param User $user
     * @return ProgrammeUser
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get User
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("user_id")
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->getUser()->getId();
    }


    /**
     * Set Row Index
     *
     * @param integer $rowIndex
     *
     * @return ProgrammeUser
     */
    public function setRowIndex($rowIndex)
    {
        $this->row_index = $rowIndex;
        return $this;
    }

    /**
     * Get Row Index
     *
     * @return int
     */
    public function getRowIndex()
    {
        return $this->row_index;
    }

    /**
     * Set Order Index
     *
     * @param integer $orderIndex
     *
     * @return ProgrammeUser
     */
    public function setOrderIndex($orderIndex)
    {
        $this->order_index = $orderIndex;
        return $this;
    }

    /**
     * Get Order Index
     *
     * @return int
     */
    public function getOrderIndex()
    {
        return $this->order_index;
    }
}
