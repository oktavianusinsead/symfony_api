<?php

namespace esuite\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseTask
 */
#[ORM\MappedSuperclass]
class BaseTask extends Base
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
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text')]
    #[Assert\NotBlank(message: 'Description cannot be blank.')]
    #[Assert\Length(max: 3000, maxMessage: 'Description cannot be longer than {{ limit }} characters.')]
    private $description;

    /**
     * @Serializer\Exclude
     **/
    #[ORM\Column(name: 'box_folder_id', type: 'string', length: 255, nullable: true)]
    private $box_folder_id;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'position', type: 'integer', nullable: false)]
    protected $position = 9999;



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
     * @return BaseTask
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
     * @return BaseTask
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
     * @return BaseTask
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
     * Get box_folder_id
     *
     * @return string
     */
    public function getBoxFolderId()
    {
        return $this->box_folder_id;
    }

    /**
     * Set box_folder_id
     *
     * @param string $box_folder_id
     * @return BaseTask
     */
    public function setBoxFolderId($box_folder_id)
    {
        $this->box_folder_id = $box_folder_id;

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
     * @return BaseTask
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }
}
