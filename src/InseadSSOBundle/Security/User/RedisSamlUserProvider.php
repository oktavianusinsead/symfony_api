<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 14/7/17
 * Time: 12:21 AM
 */

namespace InseadSSOBundle\Security\User;

use Insead\MIMBundle\Service\Redis\Saml as RedisSaml;
use Psr\Log\LoggerInterface;

use Insead\MIMBundle\Service\Redis\Base as RedisBase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use InseadSSOBundle\Security\User\RedisSamlUser;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;



final readonly class RedisSamlUserProvider implements UserProviderInterface
{
    public function __construct(private LoggerInterface $logger, private RedisSaml $redisSaml)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        $username = trim($username);
        $isInsead = strpos(strtolower($username), '@insead.edu');

        $userInfo = $this->redisSaml->getUserInfo($username);

        $this->logger->info("Userinfo from redis: " . $userInfo);

        if ($isInsead !== false) {

            if( $userInfo != "" ) {
                $userObj = json_decode($userInfo,true);
                $this->logger->info("Return loadUserByUsername: " . $userObj["username"]);
                return new RedisSamlUser($userObj["username"], $userObj["roles"]);
            }

        }

        $this->logger->info("Username $username does not exist.");
        throw new UserNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof RedisSamlUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', $user::class)
            );
        }

        $username = $user->getUsername();
        $username = trim($username);

        $isInsead = strpos(strtolower($username), '@insead.edu');

        $userInfo = $this->redisSaml->getUserInfo($username);

        $this->logger->info("Recheck userinfo from redis: " . $userInfo);

        if ($isInsead !== false) {

            if( $userInfo != "" ) {
                $userObj = json_decode($userInfo,true);

                return new RedisSamlUser($userObj["username"], $userObj["roles"]);
            }

        }

        throw new UserNotFoundException(
            sprintf('Username (refresh) "%s" does not exist.', $username)
        );
    }

    public function supportsClass($class): bool
    {
        return RedisSamlUser::class === $class;
    }
}
