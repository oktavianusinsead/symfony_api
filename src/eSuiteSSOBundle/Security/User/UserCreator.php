<?php
namespace esuiteSSOBundle\Security\User;

use esuite\MIMBundle\Service\Redis\Saml as RedisSaml;
use Psr\Log\LoggerInterface;

use LightSaml\Model\Protocol\Response;
use LightSaml\SpBundle\Security\User\UserCreatorInterface;
use LightSaml\SpBundle\Security\User\UsernameMapperInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCreator implements UserCreatorInterface
{
    /**
     * @param UsernameMapperInterface $usernameMapper
     */
    public function __construct(private readonly LoggerInterface $logger, private readonly RedisSaml $redisSaml, private $usernameMapper)
    {
    }

    /**
     * @param Response $response
     *
     * @return UserInterface|null
     */
    public function createUser(Response $response) : UserInterface|null
    {
        $username = $this->usernameMapper->getUsername($response);

        $username = strtolower((string) $username);

        $this->logger->info("Creating SAML User entry for " . $username);

        $userObj = ["username" => $username, "roles" => [
            "ROLE_USER"
        ]];

        $json = json_encode($userObj);

        $this->redisSaml->setUserInfo($username,$json);

        return new RedisSamlUser($userObj["username"], $userObj["roles"]);
    }
}
