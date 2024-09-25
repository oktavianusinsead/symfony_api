<?php

namespace Insead\MIMBundle\Service\Redis;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Vanilla extends Base
{
    protected $tokenPrefix;
    protected $ttl;
    protected $tempTtl;

    public function __construct( ParameterBagInterface $parameterBag, LoggerInterface $logger, private $secret)
    {
        parent::__construct($parameterBag, $logger);

        $this->tokenPrefix =    "vanilla:";
        $this->ttl =            60*60;
    }

    /**
     * Function to get cached VanillaRoles
     * @param String $role
     **
     * @return String
     */
    public function getRoleId($role) {
        $this->log("RETRIEVING VANILLA ROLEID for " . $role);

        $hashRole = $this->generateRandomToken($role);

        $key = $this->tokenPrefix . "role_" . $hashRole;

        $value = $this->redis->get($key);

        return $value;
    }

    /**
     * Function to cache VanillaRoles
     * @param String $role
     * @param String $value
     *
     * @return String
     */
    public function setRoleId($role,$value) {
        $this->log("SETTING VANILLA ROLEID for " . $role);

        $hashRole = $this->generateRandomToken($role);

        $key = $this->tokenPrefix . "role_" . $hashRole;

        $newValue = "";
        if( $this->redis->set($key,$value,$this->ttl) ) {
            $newValue = $this->redis->get($key);
        }

        return $newValue;
    }

    /**
     * Function to generate random token
     * @param String $string to randomize
     **
     * @return String
     */
    public function generateRandomToken($string) {
        $hashToken = hash_hmac('ripemd160', $string, (string) $this->secret);

        return $hashToken;
    }

}
