<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * FileDocument
 */
#[ORM\Table(name: 'file_documents')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class FileDocument extends Attachment
{

    /**
     *  @var string
     *  Attachment Type
     * @Serializer\Exclude
     */
    public static $ATTACHMENT_TYPE = "file_document";

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Session::class, inversedBy: 'fileDocuments', fetch: 'LAZY')]
    protected $session;

    /**
     * @var string
     */
    #[ORM\Column(name: 'box_id', type: 'string', length: 255)]
    private $box_id;

    /**
     * @var string
     *
     * @Serializer\Exclude
     */
    #[ORM\Column(name: 'path', type: 'text')]
    private $path;

    /**
     * @var string
     */
    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'A mime-type must be specified.')]
    private $mime_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'content', type: 'text')]
    private $content = "";

    /**
     * @var string
     */
    #[ORM\Column(name: 'filename', type: 'string', length: 255)]
    private $filename;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'filesize', type: 'integer')]
    private $filesize;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    private $duration = 0;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'pages', type: 'integer', nullable: true)]
    private $pages;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'is_upload_to_s3', type: 'integer', nullable: true)]
    private $is_upload_to_s3;

    /**
     * @var string
     *
     * @Serializer\Exclude
     */
    #[ORM\Column(name: 'aws_path', type: 'text')]
    private $aws_path;

    /**
     * @var string
     */
    #[ORM\Column(name: 'file_id', type: 'string', length: 255)]
    private $file_id;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserDocument::class, mappedBy: 'filedocument', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
    protected $userDocuments;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\OneToMany(targetEntity: \UserFavourite::class, mappedBy: 'filedocument', orphanRemoval: true, cascade: ['persist', 'remove', 'merge'])]
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
     * Set box_id
     *
     * @param string $boxId
     * @return FileDocument
     */
    public function setBoxId($boxId)
    {
        $this->box_id = $boxId;

        return $this;
    }

    /**
     * Get box_id
     *
     * @return string
     */
    public function getBoxId()
    {
        return $this->box_id;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return FileDocument
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set mime_type
     *
     * @param string $mime_type
     * @return FileDocument
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
     * Set content
     *
     * @param string $content
     * @return FileDocument
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * Set filename
     *
     * @param string $filename
     * @return FileDocument
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filesize
     *
     * @return integer
     */
    public function getFilesize()
    {
        return $this->filesize;
    }

    /**
     * Set filesize
     *
     * @param integer $filesize
     * @return FileDocument
     */
    public function setFilesize($filesize)
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return FileDocument
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

    /**
     * Set Pages
     *
     * @param integer $pages
     *
     * @return FileDocument
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
        return $this;
    }

    /**
     * Get Pages
     *
     * @return int
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Set is_upload_to_s3
     *
     * @param $is_upload_to_s3
     * @return FileDocument
     */
    public function setUploadToS3($is_upload_to_s3)
    {
        $this->is_upload_to_s3 = $is_upload_to_s3;

        return $this;
    }

    /**
     * Get is_upload_to_s3
     *
     * @return string
     */
    public function getUploadToS3()
    {
        return $this->is_upload_to_s3;
    }

    /**
     * Set aws_path
     *
     * @param string $aws_path
     * @return FileDocument
     */
    public function setAwsPath($aws_path)
    {
        $this->aws_path = $aws_path;

        return $this;
    }

    /**
     * Get aws_path
     *
     * @return string
     */
    public function getAwsPath()
    {
        return $this->aws_path;
    }

    /**
     * Set file_id
     *
     * @param string $fileId
     * @return FileDocument
     */
    public function setFileId($fileId)
    {
        $this->file_id = $fileId;

        return $this;
    }

    /**
     * Get file_id
     *
     * @return string
     */
    public function getFileId()
    {
        return $this->file_id;
    }
}
