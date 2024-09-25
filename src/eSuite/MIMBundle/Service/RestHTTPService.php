<?php

namespace esuite\MIMBundle\Service;
use Psr\Log\LoggerInterface;

class RestHTTPService
{

    protected $logger;

    protected $configuration;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getRequest($url, $header){
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url, $header);

        $this->logger->info('Status Code: '.json_encode($response->getStatusCode()));
        $this->logger->info('URL:'.$url);
        return json_decode($response->getBody()->getContents(),true);
    }

}
