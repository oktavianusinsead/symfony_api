<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * LinkedDocument
 */
#[ORM\Table(name: 'linked_documents')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class LinkedDocument extends Attachment
{

    /**
     *  @var string
     *  Attachment Type
     * @Serializer\Exclude
     */
    public static $ATTACHMENT_TYPE = "linked_document";

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Session::class, inversedBy: 'linkedDocuments', fetch: 'LAZY')]
    protected $session;

    /**
     * @var string
     */
    #[ORM\Column(name: 'url', type: 'text')]
    #[Assert\NotBlank(message: 'URL cannot be blank.')]
    #[Assert\Url(message: 'Invalid URL.')]
    private $url;

    /**
     * @var string
     */
    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    private $mime_type;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'expiry', type: 'datetime')]
    #[Assert\NotBlank(message: 'Expiry Date cannot be blank.')]
    private $expiry;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserDocument::class, mappedBy: 'linkeddocument', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserFavourite::class, mappedBy: 'linkeddocument', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userFavourites;


    public function __construct($data = [])
    {
        parent::__construct( self::$ATTACHMENT_TYPE, $data );
        $this->userDocuments  = new ArrayCollection();
        $this->userFavourites = new ArrayCollection();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("type")
     *
     * @return string
     */
    public function getAttachmentType()
    {
        return self::$ATTACHMENT_TYPE;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return LinkedDocument
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set mime_type
     *
     * @param string $mime_type
     * @return LinkedDocument
     */
    public function setMimeType($mime_type)
    {
        $this->mime_type = $mime_type;

        return $this;
    }

    /**
     * Get mime_type
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * Set expiry
     *
     * @param \DateTime $expiry
     * @return LinkedDocument
     */
    public function setExpiry($expiry)
    {
        $this->expiry = $expiry;

        return $this;
    }

    /**
     * Get expiry
     *
     * @return \DateTime
     */
    public function getExpiry()
    {
        return $this->expiry;
    }
}
