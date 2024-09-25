<?php

namespace Insead\MIMBundle\Service\Vanilla;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

/**
 * Vanilla Service implementation
 *     tested with:
 *          "guzzlehttp/guzzle": "~6.2"
 *          "symfony/monolog-bundle": "~2.4"
 *          "symfony/monolog-bridge": "~2.4"
 *
 * PHP version 5.6
 *
 */
class Base
{
    protected $logger;

    protected $http;

    protected $baseUrl;
    protected $apiUrl;

    protected $masterToken;
    protected $authHeader;

    protected $logUuid;

    protected $env;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->logger               = $logger;

        $this->env               = $config['symfony_environment'];
        $this->baseUrl           = $config['vanilla_base_url'];
        $this->apiUrl            = $config['vanilla_api_url'];
        $this->masterToken       = $config['vanilla_master_token'];

        $this->authHeader        = "Bearer " . $this->masterToken;

        $this->http = new Client(
            ['base_uri' => $this->baseUrl]
        );
    }

    /**
     * Function that logs a message, prefixing the Class and function name to help debug
     *
     * @param String $msg Message to be logged
     *
     **/
    protected function log($msg)
    {
        $matches = [];

        preg_match('/[^\\\\]+$/', static::class, $matches);

        if( $this->logUuid ) {
            $this->logger->info(
                $this->logUuid
                . " Vanilla Service: "
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg
            );
        } else {
            $this->logger->info(
                'Vanilla Service: '
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg);
        }
    }

    /**
     * Function that adds the Authorization header which contains the token
     *
     * @param array $options array that would be passed to Vanilla API
     *
     * @return array() options
     **/
    protected function addAuthorizationHeader($options)
    {
        if( ! isset($options['headers']))
        {
            $options['headers'] = [];
        }

        $options['headers']['Authorization'] = 'Bearer ' . $this->masterToken;

        return $options;
    }

    /**
     * Function to remember the logUuid
     * @param String $logUuid for the user
     */
    public function setLogUuid($logUuid) {
        $this->logUuid = $logUuid;
    }
}
