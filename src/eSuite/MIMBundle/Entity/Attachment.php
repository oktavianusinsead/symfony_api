<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Attachment
 */
#[ORM\MappedSuperclass]
class Attachment extends Base
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
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Title cannot be blank.')]
    protected $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text')]
    protected $description = '';

    /**
     * @var string
     */
    #[ORM\Column(name: 'position', type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: 'Position cannot be blank.')]
    protected $position;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'document_type', type: 'integer')]
    #[Assert\NotBlank(message: 'Document Type cannot be blank.')]
    protected $document_type;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'publish_at', type: 'datetime', nullable: true)]
    #[Assert\NotBlank(message: 'Publish field cannot be blank.')]
    protected $publish_at;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'due_date', type: 'datetime', nullable: true)]
    protected $due_date;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Session::class, inversedBy: 'attachments', fetch: 'LAZY')]
    protected $session;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserDocument::class, mappedBy: 'attachment', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserFavourite::class, mappedBy: 'attachment', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userFavourites;

    /**
     * Function for the constructor
     *
     * @param String $attachment_type attachment type
     * @param   array       $data   data passed
     */
    public function __construct(/**
     * @Serializer\Exclude
     */
    protected $attachment_type, $data = [])
    {
        //Call superclass (Base) constructor
        parent::__construct($data);

        $this->userDocuments = new ArrayCollection();
        $this->userFavourites = new ArrayCollection();
    }

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
     * Get userDocuments
     *
     * @return UserDocument
     */
    public function getUserDocuments()
    {
        return $this->userDocuments;
    }

    /**
     * Get userFavourites
     *
     * @return UserFavourite
     */
    public function getUserFavourites()
    {
        return $this->userFavourites;
    }

    /**
     * Set Session
     *
     * @param Session
     * @return Attachment
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set document_type
     *
     * @param integer
     * @return Attachment
     */
    public function setDocumentType($document_type)
    {
        $this->document_type = $document_type;

        return $this;
    }

    /**
     * Get document_type
     *
     * @return integer
     */
    public function getDocumentType()
    {
        return $this->document_type;
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
     * Set position
     *
     * @param string $position
     * @return Attachment
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set publish_at
     *
     * @param \DateTime $publish_at
     * @return Attachment
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
     * Set title
     *
     * @param string $title
     * @return Attachment
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Attachment
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set due_date
     *
     * @param \DateTime $due_date
     * @return Attachment
     */
    public function setDueDate($due_date)
    {
        $this->due_date = $due_date;

        return $this;
    }

    /**
     * Get due_date
     *
     * @return \DateTime
     */
    public function getDueDate()
    {
        return $this->due_date;
    }
}
