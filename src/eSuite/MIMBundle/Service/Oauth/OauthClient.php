<?php

namespace esuite\MIMBundle\Service\Oauth;

use \Psr\Log\LoggerInterface;


class OauthClient
{

    protected $curl;
    protected $logger;
    protected $url;
    protected $auth_header;

    public function __construct( array $config, LoggerInterface $logger)
    {
        $this->logger          = $logger;
        $this->curl            = curl_init();

        $this->url             = $config['url'];
        $this->auth_header     = 'Authorization: '.$config['auth_header'];

        $this->initCurl($this->url);
    }

    public function sendRequest($data) {

        $jsonData = json_encode($data);

        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $jsonData);

        $response = '';
        // try 3 times at-least before giving up
        for ($index = 0; $index < 3; $index++) {
            $response           = curl_exec($this->curl);
            $curlErrNo          = curl_errno($this->curl);

            if ($curlErrNo == 0) {
                break;
            } else {
                $curlError = curl_error($this->curl);
                $this->logger->error("Curl Error:\n $curlError");
            }
        }

        if (!$response || empty($response)) {
            $curlError       = curl_error($this->curl);
            return $curlError;
        }
        return $response;
    }

    /**
     * Setup curl resource.
     *
     * @param string $url OAuth server URL
     */
    private function initCurl($url)
    {
        $headers = ['Content-Type: application/json', $this->auth_header];

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }



}
