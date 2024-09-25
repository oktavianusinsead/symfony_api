<?php

namespace esuite\MIMBundle\Service\Barco;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

/**
 * Barco Service implementation
 *
 */
class Base
{
    protected $logger;
    protected $http;
    protected $logUuid;
    protected $env;

    protected $barcoAPIURL;
    protected $barcoAPIKey;
    protected $authHeader;
    protected $headerOptions;

    /**
     * Barco Endpoints
     */
    public $barcoUser  = 'users';
    public $barcoGroup = 'usergroups';

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->logger      = $logger;
        $this->env         = $config['symfony_environment'];
        $this->barcoAPIKey = $config["barco_weconnect_api_key"];
        $this->barcoAPIURL = $config["barco_weconnect_api_url"];

        $this->headerOptions['headers'] = ["authorization-type" => 'token', "Authorization" => "Bearer ".$this->barcoAPIKey];

        $this->http = new Client(
            ['base_uri' => $this->barcoAPIURL]
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
                . " Barco Service: "
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg
            );
        } else {
            $this->logger->info(
                'Barco Service: '
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg);
        }
    }

    /**
     * Function to remember the logUuid
     * @param String $logUuid for the user
     */
    public function setLogUuid($logUuid) {
        $this->logUuid = $logUuid;
    }
}
