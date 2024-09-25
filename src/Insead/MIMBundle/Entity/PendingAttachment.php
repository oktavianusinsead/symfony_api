<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * FileDocument
 */
#[ORM\Table(name: 'pending_attachments')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class PendingAttachment extends Base
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
    #[ORM\Column(name: 'attachment_id', type: 'integer')]
    #[Assert\NotBlank(message: 'Attachment ID cannot be blank.')]
    private $attachment_id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'attachment_type', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Attachment Type cannot be blank.')]
    private $attachment_type;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'publish_at', type: 'datetime')]
    #[Assert\NotBlank(message: 'Publish_at Date cannot be blank.')]
    private $publish_at;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Session::class, inversedBy: 'pending_attachments', fetch: 'LAZY')]
    protected $session;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'published', type: 'boolean')]
    private $published = FALSE;



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
     * @return PendingAttachment
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
     * Set attachment_id
     *
     * @param integer $attachment_id
     * @return PendingAttachment
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
     * Set attachment_type
     *
     * @param string $attachment_type
     * @return PendingAttachment
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
     * Set publish_at
     *
     * @param \DateTime $publish_at
     * @return PendingAttachment
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

    /**
     * Set published
     *
     * @param boolean $published
     * @return PendingAttachment
     */
    public function setPublished($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Get published
     *
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }
}
