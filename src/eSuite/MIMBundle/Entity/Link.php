<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Link
 */
#[ORM\Table(name: 'links')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Link extends Attachment
{

    /**
     *  @var string
     *  Attachment Type
     * @Serializer\Exclude
     */
    public static $ATTACHMENT_TYPE = "link";

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Session::class, inversedBy: 'links', fetch: 'LAZY')]
    protected $session;

    /**
     * @var string
     */
    #[ORM\Column(name: 'thumbnail', type: 'text', nullable: true)]
    private $thumbnail = "";

    /**
     * @var string
     */
    #[ORM\Column(name: 'url', type: 'text')]
    #[Assert\NotBlank(message: 'URL cannot be blank.')]
    #[Assert\Url(message: 'Invalid URL.')]
    private $url;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserDocument::class, mappedBy: 'link', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserFavourite::class, mappedBy: 'link', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
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
     * Set thumbnail
     *
     * @param string $thumbnail
     * @return Link
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /**
     * Get thumbnail
     *
     * @return string
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Link
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
}
