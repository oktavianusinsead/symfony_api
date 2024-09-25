<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 14/7/17
 * Time: 12:21 AM
 */

namespace esuiteSSOBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;


class RedisSamlUser implements UserInterface, EquatableInterface
{
    private $password;
    private $salt;

    public function __construct(private $username, private readonly array $roles)
    {
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        $this->password = '';

        return $this->password;
    }

    public function getSalt(): ?string
    {
        $this->salt = '';

        return $this->salt;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function eraseCredentials(): void
    {
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof RedisSamlUser) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        if ($this->roles !== $user->getRoles()) {
            return false;
        }

        return true;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
