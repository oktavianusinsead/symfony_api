<?php

namespace esuite\MIMBundle\Service\Redis;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthToken extends Base
{
    protected $tokenPrefix;
    protected $ttl;

    private static $OPENSSL_CIPHER_NAME = "aes-128-cbc"; //Name of OpenSSL Cipher
    private static $CIPHER_KEY_LEN = 16; //128 bits

    public function __construct( ParameterBagInterface $parameterBag, LoggerInterface $logger, private $secret)
    {
        parent::__construct($parameterBag, $logger);

        $this->tokenPrefix =    "auth:";
        $this->ttl =            10*60;
    }

    /**
     * Function to get auth user info
     * @param String $token token attached to the request
     **
     * @return String
     */
    public function getUserInfo($token) {
        $this->log("RETRIEVING Auth Info for  " . $token);

        $hashToken = hash_hmac('ripemd160', $token, (string) $this->secret);

        $key = $this->tokenPrefix . "user_" . $hashToken;

        $value = $this->redis->get($key);

        return $value;
    }

    /**
     * Function to save auth user info
     * @param String $token token attached to the request
     * @param String $value value to be saved
     *
     * @return String
     */
    public function setUserInfo($token,$value) {
        $this->log("SETTING NEW Auth Info for " . $token);

        $hashToken = hash_hmac('ripemd160', $token, (string) $this->secret);

        $key = $this->tokenPrefix . "user_" . $hashToken;

        $newValue = "";
        if( $this->redis->set($key,$value,$this->ttl) ) {
            $newValue = $this->redis->get($key);
        }

        return $newValue;
    }

    /**
     * Function to save auth user info
     * @param String $token token attached to the request
     * @param String $value value to be saved
     *
     * @return String
     */
    public function clearUserInfo($token) {
        $this->log("Clearing Auth Info for " . $token);

        $hashToken = hash_hmac('ripemd160', $token, (string) $this->secret);

        $key = $this->tokenPrefix . "user_" . $hashToken;

        $this->redis->set($key,"",1);

        return "";
    }



    /**
     * Encrypt data using AES Cipher (CBC) with 128 bit key
     *
     * reference: https://github.com/chaudhuri-ab/CrossPlatformCiphers/blob/master/PHP_CIPHER/PHP_CIPHER/index.php
     *
     * @param String $key - key to use should be 16 bytes long (128 bits)
     * @param String $iv - initialization vector
     * @param String $data - data to encrypt
     * @return String encrypted data in base64 encoding with iv attached at end after a :
     */
    public function encrypt($key, $iv, $data) {
        if (strlen($key) < self::$CIPHER_KEY_LEN) {
            $key = str_pad("$key", self::$CIPHER_KEY_LEN, "0"); //0 pad to len 16
        } else if (strlen($key) > self::$CIPHER_KEY_LEN) {
            $key = substr($key, 0, self::$CIPHER_KEY_LEN); //truncate to 16 bytes
        }

        $encodedEncryptedData = base64_encode(openssl_encrypt($data, self::$OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, $iv));
        $encodedIV = base64_encode($iv);
        $encryptedPayload = $encodedEncryptedData.":".$encodedIV;

        return $encryptedPayload;

    }
    /**
     * Decrypt data using AES Cipher (CBC) with 128 bit key
     *
     * reference: https://github.com/chaudhuri-ab/CrossPlatformCiphers/blob/master/PHP_CIPHER/PHP_CIPHER/index.php
     *
     * @param String $key - key to use should be 16 bytes long (128 bits)
     * @param String $data - data to be decrypted in base64 encoding with iv attached at the end after a :
     * @return String decrypted data
     */
    public function decrypt($key, $data) {
        if (strlen($key) < self::$CIPHER_KEY_LEN) {
            $key = str_pad("$key", self::$CIPHER_KEY_LEN, "0"); //0 pad to len 16
        } else if (strlen($key) > self::$CIPHER_KEY_LEN) {
            $key = substr($key, 0, self::$CIPHER_KEY_LEN); //truncate to 16 bytes
        }

        $parts = explode(':', $data); //Separate Encrypted data from iv.
        $decryptedData = openssl_decrypt(base64_decode($parts[0]), self::$OPENSSL_CIPHER_NAME, $key, OPENSSL_RAW_DATA, base64_decode($parts[1]));

        return $decryptedData;
    }
}
