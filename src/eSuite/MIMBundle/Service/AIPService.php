<?php

namespace esuite\MIMBundle\Service;

use Exception;

use Aws\Sns\SnsClient;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Request;

use esuite\MIMBundle\Entity\Course;

class AIPService
{

    protected $logger;

    protected $configuration;

    protected $restHTTPService;

    protected $baseURL;
    protected $defaultHeader;

    protected $personBaseURL;
    protected $personDefaultHeader;

    public function __construct(LoggerInterface $logger, private $config,RestHTTPService $restHTTPService)
    {
        $this->logger = $logger;
        $this->configuration = $config;
        $this->restHTTPService = $restHTTPService;
        $this->personBaseURL = $config['aip_person_base_url'];
        $this->personDefaultHeader = [
            'headers'        => ['client_id' => $config['aip_person_client_id'], 'client_secret' => $config['aip_person_client_secret'], 'Cache-Control' => 'no-cache'],
            'decode_content' => false
        ];

        $this->baseURL      = $config['aip_base_url'];
        $this->defaultHeader = [
            'headers'        => ['client_id' => $config['aip_client_id'], 'client_secret' => $config['aip_client_secret'], 'Cache-Control' => 'no-cache'],
            'decode_content' => false
        ];

        
    }

    /**
     * Function to get a User's profile info
     * @param String $peoplesoftId Peoplesoft id of the user whose profile data is requested
     *
     * @return mixed that contains User's profile info
     */
    public function getUserApi($peoplesoftId)
    {
        $userURL = $this->baseURL."users/".$peoplesoftId;
        return $this->restHTTPService->getRequest($userURL,$this->defaultHeader);
    }

    /**
     * Function to get a User's profile info
     * @param String $upn esuite email of the user whose profile data is requested
     *
     * @return void () that contains User's profile info
     */
    public function getUserByEMailApi($upn)
    {
        $userURL = $this->baseURL."users/search?email=".$upn;
        return $this->restHTTPService->getRequest($userURL,$this->defaultHeader);
    }

    public function getCourseDetail($class_number,$term)
    {
        $userURL = $this->baseURL."courses?class_number=".$class_number."&term=".$term;
        
        return $this->restHTTPService->getRequest($userURL,$this->defaultHeader);
    }

    public function getEnrollment($class_number,$term)
    {
        $userURL = $this->baseURL."enrollments?class_number=".$class_number."&term=".$term;
        
        return $this->restHTTPService->getRequest($userURL,$this->defaultHeader);
    }

    public function getPersonDetail($peoplesoftId) {
        $url = $this->personBaseURL."/api/person?source=meta&psft_id=".$peoplesoftId;

        return $this->restHTTPService->getRequest($url,$this->personDefaultHeader);
    }

    public function getPersonDetailByType($peoplesoftId, $type) {
        try {
            $url = $this->personBaseURL . "/api/person?source=" . $type . "&psft_id=" . $peoplesoftId;
            $response = $this->restHTTPService->getRequest($url, $this->personDefaultHeader);
            if (key_exists($this->config['aip_person_keys'][$type], $response)) {
                return [$type => $response[$this->config['aip_person_keys'][$type]]];
            } else {
                return $response;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }
}
