<?php
/**
 * Created by PhpStorm.
 * User: jeffersonmartin
 * Date: 1/28/19
 * Time: 3:41 PM
 */

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Validator\Constraints as Assert;
use Insead\MIMBundle\Annotations\Validator as FormAssert;

/**
 * VanillaProgrammeDiscussion
 */
#[ORM\Table(name: 'vanillaprogrammediscussion')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class VanillaProgrammeDiscussion extends Base
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
    #[ORM\Column(name: 'vanilla_discussion_id', type: 'string', length: 255, nullable: true)]
    protected $vanillaDiscussionId;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'programme_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: \Programme::class, inversedBy: 'programmediscussions')]
    protected $programme;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    protected $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text')]
    #[Assert\NotBlank(message: 'Description cannot be blank.')]
    protected $description;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'groupId', type: 'integer')]
    protected $groupId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'url', type: 'text')]
    protected $url;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'isClosed', type: 'boolean')]
    private $closed = FALSE;

    public function __construct()
    {
        parent::__construct();
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
     * Set programme
     *
     * @param Programme $programme
     * @return VanillaProgrammeDiscussion
     */
    public function setProgramme($programme)
    {
        $this->programme = $programme;

        return $this;
    }

    /**
     * Get programme
     *
     * @return Programme
     */
    public function getProgramme()
    {
        return $this->programme;
    }

    /**
     * Set vanillaDiscussionId
     *
     * @param String $vanillaDiscussionId
     * @return VanillaProgrammeDiscussion
     */
    public function setVanillaDiscussionId($vanillaDiscussionId)
    {
        $this->vanillaDiscussionId = $vanillaDiscussionId;

        return $this;
    }

    /**
     * Get vanillaDiscussionId
     *
     * @return String
     */
    public function getVanillaDiscussionId()
    {
        return $this->vanillaDiscussionId;
    }

    /**
     * Set name
     *
     * @param String $name
     * @return VanillaProgrammeDiscussion
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set closed
     *
     * @param $closed
     * @return VanillaProgrammeDiscussion
     */
    public function setClosed($closed)
    {
        $this->closed = $closed;

        return $this;
    }

    /**
     * Get closed
     *
     * @return boolean
     */
    public function getClosed()
    {
        return $this->closed;
    }


    /**
     * Set description
     *
     * @param String $description
     * @return VanillaProgrammeDiscussion
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return String
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set groupId
     *
     * @param Integer $groupId
     * @return VanillaProgrammeDiscussion
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return Integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set discussion url
     *
     *
     * @return VanillaProgrammeDiscussion
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get discussion url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
