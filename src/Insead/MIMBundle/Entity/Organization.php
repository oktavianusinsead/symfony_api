<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Organization
 */
#[ORM\Table(name: 'organizations')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Organization extends Base
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
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    private $title;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ext_org_id', type: 'string')]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    private $ext_org_id;


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
     * Set title
     *
     * @param string $title
     * @return Organization
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
     * Set ext org id
     *
     * @param string $ext_org_id
     * @return Organization
     */
    public function setExtOrgId($ext_org_id)
    {
        $this->ext_org_id = $ext_org_id;
        return $this;
    }

    /**
     * Get ext org id
     * @return string
     */
    public function getExtOrgId()
    {
        return $this->ext_org_id ;
    }



}
