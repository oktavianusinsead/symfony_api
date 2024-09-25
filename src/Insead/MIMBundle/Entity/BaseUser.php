<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BaseUser
 */
#[ORM\MappedSuperclass]
class BaseUser extends Base
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     *
     * @var user
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'baseUser')]
    protected $user;

    /**
     * FileDocument
     *
     *
     * @var filedocument
     */
    #[ORM\JoinColumn(name: 'filedocument_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \FileDocument::class, inversedBy: 'baseUser')]
    protected $filedocument;


    /**
     * Link
     *
     *
     * @var link
     */
    #[ORM\JoinColumn(name: 'link_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Link::class, inversedBy: 'baseUser')]
    protected $link;


    /**
     * LinkedDocument
     *
     *
     * @var linkeddocument
     */
    #[ORM\JoinColumn(name: 'linkeddocument_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \LinkedDocument::class, inversedBy: 'baseUser')]
    protected $linkeddocument;

    /**
     * Video
     *
     *
     * @var video
     */
    #[ORM\JoinColumn(name: 'video_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Video::class, inversedBy: 'baseUser')]
    protected $video;

    /**
     * @var string
     *-
     */
    #[ORM\Column(name: 'document_type', type: 'string', length: 255, nullable: false)]
    protected $document_type;

    /**
     *
     * @var Course
     */
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Course::class, inversedBy: 'baseUser')]
    protected $course;


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
     * Set user
     *
     * @param User $user
     * @return BaseUser
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set FileDocument
     *
     * @param FileDocument $fileDocument
     * @return BaseUser
     */
    public function setFileDocument($fileDocument)
    {
        $this->filedocument = $fileDocument;

        return $this;
    }

    /**
     * Get FileDocument
     *
     * @return FileDocument
     */
    public function getFileDocument()
    {
        return $this->filedocument;
    }

    /**
     * Set Link
     *
     * @param Link $link
     * @return BaseUser
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get Link
     *
     * @return Link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set LinkedDocument
     *
     * @param LinkedDocument $linkedDocument
     *
     * @return BaseUser
     */
    public function setLinkDocument($linkedDocument)
    {
        $this->linkeddocument = $linkedDocument;

        return $this;
    }

    /**
     * Get LinkedDocument
     *
     * @return LinkedDocument
     */
    public function getLinkDocument()
    {
        return $this->linkeddocument;
    }

    /**
     * Set Video
     *
     * @param Video $video
     *
     * @return BaseUser
     */
    public function setVideo($video)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Get Video
     *
     * @return Video
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Set Document Type
     *
     * @param integer $document_type
     * @return BaseUser
     */
    public function setDocumentType($document_type)
    {
        $this->document_type = $document_type;

        return $this;
    }

    /**
     * Get Document Type
     *
     * @return integer
     */
    public function getDocumentType()
    {
        return $this->document_type;
    }

    /**
     * Set Course
     *
     * @param Course $course
     *
     * @return BaseUser
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get Course
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }
}
