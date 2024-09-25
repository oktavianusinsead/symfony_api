<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use esuite\MIMBundle\Annotations\Validator as FormAssert;

/**
 * GroupSessionAttachment
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: 'groups_sessions_attachments')]
#[ORM\UniqueConstraint(name: 'group_session_attachments_unique', columns: ['session_id', 'group_session_id', 'attachment_type', 'attachment_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class GroupSessionAttachment extends Base
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
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Session::class, inversedBy: 'group_sessions_attachments', fetch: 'LAZY')]
    protected $session;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'group_session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \GroupSession::class, inversedBy: 'group_session_attachments', fetch: 'LAZY')]
    protected $group_session;

    /**
     * @var string
     */
    #[ORM\Column(name: 'attachment_type', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Attachment Type cannot be blank.')]
    private $attachment_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'attachment_id', type: 'integer')]
    #[Assert\NotBlank(message: 'Attachment ID cannot be blank.')]
    private $attachment_id;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'publish_at', type: 'datetime')]
    #[Assert\NotBlank(message: 'Publish_at Date cannot be blank.')]
    private $publish_at;



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
     * Set Session
     *
     * @param Session $session
     * @return GroupSessionAttachment
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get Session
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("session_id")
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->getSession()->getId();
    }

    /**
     * Set GroupSession
     *
     * @param GroupSession $group_session
     * @return GroupSessionAttachment
     */
    public function setGroupSession($group_session)
    {
        $this->group_session = $group_session;

        return $this;
    }

    /**
     * Get GroupSession
     *
     * @return GroupSession
     */
    public function getGroupSession()
    {
        return $this->group_session;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("group_session_id")
     *
     * @return string
     */
    public function getGroupSessionId()
    {
        return $this->getGroupSession()->getId();
    }

    /**
     * Set attachment_type
     *
     * @param string $attachment_type
     * @return GroupSessionAttachment
     */
    public function setAttachmentType($attachment_type)
    {
        $this->attachment_type = $attachment_type;

        return $this;
    }

    /**
     * Get attachment_type
     *
     * @return string
     */
    public function getAttachmentType()
    {
        return $this->attachment_type;
    }

    /**
     * Set attachment_id
     *
     * @param integer $attachment_id
     * @return GroupSessionAttachment
     */
    public function setAttachmentId($attachment_id)
    {
        $this->attachment_id = $attachment_id;

        return $this;
    }

    /**
     * Get attachment_id
     *
     * @return integer
     */
    public function getAttachmentId()
    {
        return $this->attachment_id;
    }

    /**
     * Set publish_at
     *
     * @param \DateTime $publish_at
     * @return GroupSessionAttachment
     */
    public function setPublishAt($publish_at)
    {
        $this->publish_at = $publish_at;

        return $this;
    }

    /**
     * Get publish_at
     *
     * @return \DateTime
     */
    public function getPublishAt()
    {
        return $this->publish_at;
    }
}
