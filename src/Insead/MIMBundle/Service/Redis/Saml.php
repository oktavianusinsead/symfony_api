<?php

namespace Insead\MIMBundle\Service\Redis;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Saml extends Base
{
    protected string $tokenPrefix;
    protected int $ttl;
    protected int $ttlSamlSession;
    protected int $tempTtl;

    public function __construct( ParameterBagInterface $parameterBag, LoggerInterface $logger, private readonly mixed $secret)
    {
        parent::__construct($parameterBag, $logger);

        $this->tokenPrefix    = "saml:";
        $this->ttl            = 15*60; //15 minutes
        $this->ttlSamlSession = 8*60; //480 minutes = 8hrs
        $this->tempTtl        = 2*60;
    }

    /**
     * Function to get saml user info
     * @param String $email email address of the user
     **
     * @return String
     */
    public function getUserInfo(string $email): string
    {
        $email = trim($email);
        $email = strtolower($email);
        $this->log("RETRIEVING SAML Info for  " . $email);

        $hashEmail = hash_hmac('ripemd160', $email, (string) $this->secret);

        $key = $this->tokenPrefix . "user_" . $hashEmail;

        return $this->redis->get($key);
    }

    /**
     * Function to save saml user info
     * @param String $email email address of the user
     * @param String $value value to be saved
     *
     * @return String
     */
    public function setUserInfo(string $email,string $value): string
    {
        $email = trim($email);
        $email = strtolower($email);
        $this->log("SETTING NEW SAML Info for " . $email);

        $hashEmail = hash_hmac('ripemd160', $email, (string) $this->secret);

        $key = $this->tokenPrefix . "user_" . $hashEmail;

        $newValue = "";
        if( $this->redis->set($key,$value,$this->ttlSamlSession) ) {
            $newValue = $this->redis->get($key);
        }

        return $newValue;
    }

    /**
     * Removal of saml user once authenticated
     */
    public function removeSAMLUser(string $email)
    {
        $email = trim($email);
        $email = strtolower($email);
        $this->log("Removing SAML Info for " . $email);

        $hashEmail = hash_hmac('ripemd160', $email, (string) $this->secret);

        $key = $this->tokenPrefix . "user_" . $hashEmail;

        if ($this->redis->del($key)){
            $this->log("The user has been removed: ".$email);
        } else {
            $this->log("Redis key not exists: ".$key);
        }
    }

    /**
     * Function to get saml id entry
     * @param String $id id entry of the SamlResponse
     **
     * @return String
     */
    public function getIdEntry(string $id): mixed
    {
        $id = trim($id);
        $this->log("RETRIEVING SAML IdEntry Info for  " . $id);

        $hashId = hash_hmac('ripemd160', $id, (string) $this->secret);

        $key = $this->tokenPrefix . "identry_" . $hashId;

        return $this->redis->get($key);
    }

    /**
     * Function to save saml id entry
     * @param String $id id entry of the samlresponse
     * @param String $value value to be saved
     *
     * @return String
     */
    public function setIdEntry(string $id, string $value): string
    {
        $id = trim($id);
        $this->log("SETTING NEW SAML IdEntry Info for " . $id);

        $hashId = hash_hmac('ripemd160', $id, (string) $this->secret);

        $key = $this->tokenPrefix . "identry_" . $hashId;

        $newValue = "";
        if( $this->redis->set($key,$value,$this->ttl) ) {
            $newValue = $this->redis->get($key);
        }

        return $newValue;
    }

    /**
     * Function to delete saml id entry
     * @param String $id id entry of the samlresponse
     */
    public function deleteIdEntry(string $id) {
        $id = trim($id);
        $this->log("Deleting IdEntry for key " . $id);

        $hashId = hash_hmac('ripemd160', $id, (string) $this->secret);

        $key = $this->tokenPrefix . "identry_" . $hashId;

        if ($this->redis->del($key)){
            $this->log("ID Entry: $id has been removed");
        } else {
            $this->log("ID Entry: $id does not exists");
        }
    }

    /**
     * Function to get saml user info for a given temptoken
     * @param String $token random token obtained during sso-acs
     **
     * @return String
     */
    public function getUserTempInfo(string $token): mixed
    {
        $token = trim($token);
        $this->log("Retrieving user info for  " . $token);

        $hashToken = hash_hmac('ripemd160', $token, (string) $this->secret);

        $key = $this->tokenPrefix . "temp_" . $hashToken;

        return $this->redis->get($key);
    }

    /**
     * Function to save saml user info for a given temptoken
     * @param String $token random token obtained during sso-acs
     * @param String $value value to be saved
     *
     * @return String
     */
    public function setUserTempInfo(string $token, string $value): mixed
    {
        $token = trim($token);
        $this->log("Saving user info for " . $token);

        $hashToken = hash_hmac('ripemd160', $token, (string) $this->secret);

        $key = $this->tokenPrefix . "temp_" . $hashToken;

        $newValue = "";
        if( $this->redis->set($key,$value,$this->tempTtl) ) {
            $newValue = $this->redis->get($key);
        }

        return $newValue;
    }

    /**
     * Function to remove saml user info for a given temptoken
     * @param String $token random token obtained during sso-acs
     *
     */
    public function removeUserTempInfo(string $token)
    {
        $token = trim($token);
        $hashToken = hash_hmac('ripemd160', $token, (string) $this->secret);
        $key = $this->tokenPrefix . "temp_" . $hashToken;

        if ($this->redis->del($key)){
            $this->log("Temp token has been removed: ".$key);
        } else {
            $this->log("Temp does not exists: ".$key);
        }
    }

    /**
     * Function to generate random token
     * @param String $string to randomize
     **
     * @return String
     */
    public function generateRandomToken(string $string): string
    {
        $string = trim($string);
        return hash_hmac('ripemd160', $string, (string) $this->secret);
    }

    public function setLogoutTransactionRequest(string $token, string $sessionIndex)
    {
        $token = trim($token);
        $key = $this->tokenPrefix . "logout_tran_" . $token;
        $this->redis->set($key,$sessionIndex,$this->ttl);
    }

    public function getLogoutTransactionRequest(string $token)
    {
        $token = trim($token);
        $key = $this->tokenPrefix . "logout_tran_" . $token;
        return $this->redis->get($key);
    }
}
