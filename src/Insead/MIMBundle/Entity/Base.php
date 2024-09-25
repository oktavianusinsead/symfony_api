<?php

namespace Insead\MIMBundle\Entity;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use JsonSerializable;

use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;

use OpenApi\Annotations as OA;

/**
 * @Serializer\ExclusionPolicy("None")
 */
#[ORM\HasLifecycleCallbacks]
abstract class Base implements JsonSerializable
{
    private readonly SerializerInterface $serializer;

    /**
     * @var \DateTime
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected $created;

    /**
     * @var \DateTime
     *
     * @Serializer\Exclude
     *
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    protected $updated;

    /**
     *  @var boolean
     *  If true, entire object and sub-entities are serialized.
     *  If false, only ids of sub-entities are serialized
     *  @Serializer\Exclude
     */
    protected $serializeFully = FALSE;

    /**
     *  @var boolean
     *  If true, serialize only published sub-entities.
     *  If false, serialize all sub-entities
     *  @Serializer\Exclude
     */
    protected $serializePublishedSubEntities = FALSE;

    /**
     *   @param boolean $serializeFully
     *
     * @return Base
     */
    public function serializeFullObject($serializeFully)
    {
        $this->serializeFully = $serializeFully;

        return $this;
    }

    /**
     *   @param boolean $serializePublishedSubEntities
     *
     * @return Base
     */
    public function serializeOnlyPublished($serializePublishedSubEntities)
    {
        $this->serializePublishedSubEntities = $serializePublishedSubEntities;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    protected abstract function getId();

    /**
     *   You can create a new entity class and pre-seed it with data.
     *   $object = new MyEntity(['option1'=>'test','option2'=>'test']);
     *
     * @param array $data data passed to the constructor
     *
     *
     * @throws \Exception
     */
    public function __construct($data = [])
    {
        $this->setCreated(new \DateTime());
        $this->setUpdated(new \DateTime());
    }

    /**
     * Set Created
     *
     * @param string $created
     * @return Base
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get Created
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("created_at")
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set Updated
     *
     * @param string $updated
     * @return Base
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get Updated
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("updated_at")
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setUpdatedValue() {
        // update the modified time
        $this->setUpdated(new \DateTime());
        return $this;
    }

    /**
     * Set peoplesoft_id
     *
     * @param string $peoplesoft_id
     */
    public function setPeoplesoftId($peoplesoft_id)
    {
        $this->peoplesoft_id = $peoplesoft_id;
        return $this;
    }

    /**
     * Get peoplesoft_id
     *
     * @return string
     */
    public function getPeoplesoftId()
    {
        return $this->peoplesoft_id ?: '';
    }

    /**
     *   $object = new MyEntity();
     *   $object->setOption1('test');
     *   print_r($object->serialize()); // ['option1'=>'test', 'option2'=>'']
     **/
    public function serialize(): array
    {
        $serializer = SerializerBuilder::create()->build();
        return $serializer->toArray($this);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->serialize();
    }

}
