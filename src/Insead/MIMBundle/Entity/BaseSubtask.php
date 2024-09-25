<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SubTask
 */
#[ORM\MappedSuperclass]
class BaseSubtask extends Base
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
     * @var integer
     */
    #[ORM\Column(name: 'subtask_type', type: 'integer')]
    #[Assert\NotBlank(message: 'Please select a Type.')]
    protected $subtask_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'url', type: 'text', nullable: true)]
    protected $url;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'filesize', type: 'integer', nullable: true)]
    protected $filesize;

    /**
     * @var string
     */
    #[ORM\Column(name: 'filename', type: 'string', length: 255, nullable: true)]
    protected $filename;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mime_type', type: 'string', length: 255, nullable: true)]
    protected $mime_type;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'pages', type: 'integer', nullable: true)]
    protected $pages;

    /**
     * @var string
     */
    #[ORM\Column(name: 'box_id', type: 'string', length: 255, nullable: true)]
    protected $box_id;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'position', type: 'integer', nullable: false)]
    private $position = 9999;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email_send_To', type: 'string', length: 255, nullable: true)]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email.")]
    protected $email_send_to;

    /**
     * @var string
     */
    #[ORM\Column(name: 'email_subject', type: 'string', length: 255, nullable: true)]
    protected $email_subject;

    /**
     * @var string
     */
    #[ORM\Column(name: 'embedded_content', type: 'text', nullable: true)]
    private $embedded_content;

    /**
     * @return string|null
     */
    public function getEmailSendTo(): string|null
    {
        return $this->email_send_to;
    }

    
    public function setEmailSendTo(string $email_send_to): void
    {
        $this->email_send_to = $email_send_to;
    }

    /**
     * @return string|null
     */
    public function getEmailSubject(): string|null
    {
        return $this->email_subject;
    }

    public function setEmailSubject(string $email_subject): void
    {
        $this->email_subject = $email_subject;
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
     * Set id
     *
     * @param integer $id
     * @return BaseSubTask
     */
    public function setId($id=null)
    {
        $this->id = $id;
        return $this;
    }


    /**
     * Set title
     *
     * @param string $title
     * @return BaseSubTask
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
     * Set subtask_type
     *
     * @param integer $subtaskType
     * @return BaseSubTask
     */
    public function setSubtaskType($subtaskType)
    {
        $this->subtask_type = $subtaskType;

        return $this;
    }

    /**
     * Get subtask_type
     *
     * @return integer
     */
    public function getSubtaskType()
    {
        return $this->subtask_type;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return BaseSubTask
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
     * @return BaseSubTask
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
     * Set filename
     *
     * @param string $filename
     * @return BaseSubTask
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Set mime_type
     *
     * @param string $mime_type
     * @return BaseSubTask
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
     * Set Pages
     *
     * @param integer $pages
     *
     * @return BaseSubTask
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
     * Set box_id
     *
     * @param string $boxId
     * @return BaseSubTask
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
     * @return string
     */
    public function getEmbeddedContent()
    {
        return $this->embedded_content;
    }

    /**
     * @param string $embedded_content
     *
     * @return BaseSubTask
     */
    public function setEmbeddedContent($embedded_content)
    {
        $this->embedded_content = $embedded_content;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     *
     * @return BaseSubTask
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

}
