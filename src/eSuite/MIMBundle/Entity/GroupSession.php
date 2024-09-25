<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use esuite\MIMBundle\Annotations\Validator as FormAssert;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * GroupSession
 *
 * @FormAssert\DateRange()
 */
#[ORM\Table(name: 'groups_sessions')]
#[ORM\UniqueConstraint(name: 'group_session_unique', columns: ['group_id', 'session_id'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class GroupSession extends BaseGroup
{
    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Session::class, inversedBy: 'group_sessions', fetch: 'LAZY')]
    protected $session;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Group::class, inversedBy: 'group_sessions', fetch: 'LAZY')]
    protected $group;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'handouts_published', type: 'boolean')]
    private $handouts_published = FALSE;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \GroupSessionAttachment::class, mappedBy: 'group_session', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $group_session_attachments;

    public function __construct()
    {
        parent::__construct();
        $this->setDefaults();
    }

    public function setDefaults() {
        $this->group_session_attachments   = new ArrayCollection();
    }

    /**
     * Get list of all Group Session Attachments
     *
     * @return array()
     */
    public function getGroupSessionAttachments()
    {
        $items = [];

        if ($this->group_session_attachments) {
            foreach($this->group_session_attachments as $attachment) {
                array_push($items, $attachment);
            }
        }

        return $items;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("group_session_attachments")
     *
     * @return array()
     */
    public function getGroupSessionsObjects()
    {
        $items = [];

        foreach($this->getGroupSessionAttachments() as $attachment) {
            array_push($items, $attachment->getId());
        }

        return $items;
    }

    /**
     * Set Session
     *
     * @param Session $session
     * @return GroupSession
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
     * Set handouts_published
     *
     * @param boolean $handouts_published
     * @return GroupSession
     */
    public function setHandoutsPublished($handouts_published)
    {
        $this->handouts_published = $handouts_published;

        return $this;
    }

    /**
     * Get handouts_published
     *
     * @return boolean
     */
    public function getHandoutsPublished()
    {
        return $this->handouts_published;
    }
}
