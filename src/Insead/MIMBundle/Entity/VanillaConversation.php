<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

/**
 * VanillaConversation
 */
#[ORM\Table(name: 'vanillaconversation')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class VanillaConversation extends Base
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\JoinColumn(name: 'programme', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Programme::class, inversedBy: 'programmeVanillaConversation')]
    protected $programme;

    /**
     * User who initiate the conversation
     */
    #[ORM\JoinColumn(name: 'user', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'userVanillaConversation')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'userList', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Users in conversation should not be blank')]
    protected $userList;

    /**
     * The ID of the conversation.
     *
     * @var integer
     */
    #[ORM\Column(name: 'conversationID', type: 'integer', nullable: true)]
    protected $conversationID;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isProcessed', type: 'boolean', options: ['default' => 0], nullable: false)]
    protected $processed;

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
     * @param Programme
     *
     * @return VanillaConversation
     */
    public function setProgramme($programme)
    {
        $this->programme = $programme;
        return $this;
    }

    /**
     * @return Programme
     */
    public function getProgramme()
    {
        return $this->programme;
    }

    /**
     * @param User
     *
     * @return VanillaConversation
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return VanillaConversation
     */
    public function setUserList(string $userList)
    {
        $this->userList = $userList;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserList()
    {
        return $this->userList;
    }

    /**
     * @return VanillaConversation
     */
    public function setConversationID(int $conversationID)
    {
        $this->conversationID = $conversationID;

        return $this;
    }

    /**
     * @return int
     */
    public function getConversationID()
    {
        return $this->conversationID;
    }

    /**
     * @return VanillaConversation
     */
    public function setProcessed(bool $processed)
    {
        $this->processed = $processed;

        return $this;
    }

    /**
     * @return bool
     */
    public function processed(): bool
    {
        return $this->processed;
    }

}

