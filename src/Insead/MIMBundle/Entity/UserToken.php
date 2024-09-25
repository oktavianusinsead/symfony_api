<?php

namespace Insead\MIMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * UserTokens
 */
#[ORM\Table(name: 'user_tokens')]
#[ORM\Index(name: 'oauth_access_token_idx', columns: ['oauth_access_token'])]
#[ORM\Index(name: 'refresh_token_idx', columns: ['refresh_token'])]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class UserToken extends Base
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
    #[ORM\Column(name: 'access_token', type: 'text')]
    private $access_token;

    /**
     * @var string
     */
    #[ORM\Column(name: 'refresh_token', type: 'string', length: 255)]
    private $refresh_token;

    /**
     * @var string
     */
    #[ORM\Column(name: 'oauth_access_token', type: 'string', length: 255)]
    private $oauth_access_token;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'token_expiry', type: 'datetime', nullable: true)]
    private $token_expiry;

    /**
     * @var string
     */
    #[ORM\Column(name: 'scope', type: 'string')]
    private $scope;

    /**
     * @var string
     */
    #[ORM\Column(name: 'session_index', type: 'string', nullable: true)]
    private $session_index;

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
     * @return UserToken
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
     * Set access_token
     *
     * @param string $accessToken
     * @return UserToken
     */
    public function setAccessToken($accessToken)
    {
        $this->access_token = $accessToken;

        return $this;
    }

    /**
     * Get access_token
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * Set refresh_token
     *
     * @param string $refreshToken
     * @return UserToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refresh_token = $refreshToken;

        return $this;
    }

    /**
     * Get refresh_token
     *
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * Set oauth_access_token
     *
     * @param string $oauthAccessToken
     * @return UserToken
     */
    public function setOauthAccessToken($oauthAccessToken)
    {
        $this->oauth_access_token = $oauthAccessToken;

        return $this;
    }

    /**
     * Get oauth_access_token
     *
     * @return string
     */
    public function getOauthAccessToken()
    {
        return $this->oauth_access_token;
    }

    /**
     * Set token_expiry
     *
     * @param \DateTime $tokenExpiry
     * @return UserToken
     */
    public function setTokenExpiry($tokenExpiry)
    {
        $this->token_expiry = $tokenExpiry;

        return $this;
    }

    /**
     * Get token_expiry
     *
     * @return \DateTime
     */
    public function getTokenExpiry()
    {
        return $this->token_expiry;
    }

    /**
     * Set scope
     *
     * @param string $scope
     * @return UserToken
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
     * Set session_index
     *
     * @param string $session_index
     * @return UserToken
     */
    public function setSessionIndex($session_index)
    {
        $this->session_index = $session_index;

        return $this;
    }

    /**
     * Get session_index
     *
     * @return string
     */
    public function getSessionIndex()
    {
        return $this->session_index;
    }
}
