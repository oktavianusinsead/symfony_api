<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * ArchivedUserTokens
 */
#[ORM\Table(name: 'archived_user_tokens')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ArchivedUserToken extends Base
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @Serializer\Exclude
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User::class, inversedBy: 'user_tokens')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'scope', type: 'string')]
    private $scope;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'refreshable', type: 'boolean')]
    private $refreshable = FALSE;


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
     * @return ArchivedUserToken
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
     * Set scope
     *
     * @param string $scope
     * @return ArchivedUserToken
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set refreshable
     *
     * @param boolean $refreshable
     * @return ArchivedUserToken
     */
    public function setRefreshable($refreshable)
    {
        $this->refreshable = $refreshable;

        return $this;
    }

    /**
     * Get refreshable
     *
     * @return boolean
     */
    public function getRefreshable()
    {
        return $this->refreshable;
    }
}
