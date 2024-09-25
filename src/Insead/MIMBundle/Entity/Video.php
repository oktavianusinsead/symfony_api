<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Video
 */
#[ORM\Table(name: 'videos')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Video extends Attachment
{
    /**
     *  @var string
     *  Attachment Type
     *  @Serializer\Exclude
     */
    public static $ATTACHMENT_TYPE = "video";

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Session::class, inversedBy: 'videos', fetch: 'LAZY')]
    protected $session;

    /**
     * @var string
     */
    #[ORM\Column(name: 'url', type: 'text')]
    #[Assert\NotBlank(message: 'URL cannot be blank.')]
    private $url;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'duration', type: 'integer')]
    private $duration;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserDocument::class, mappedBy: 'video', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserFavourite::class, mappedBy: 'video', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
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
     * @return Video
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
     * Set duration
     *
     * @param integer $duration
     * @return Video
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }
}
