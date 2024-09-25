<?php

namespace esuite\MIMBundle\Service\Vanilla;

use Psr\Log\LoggerInterface;
use Exception;
use esuite\MIMBundle\Exception\VanillaGenericException;
use Symfony\Component\HttpFoundation\Response;

class Discussion extends Base
{
    protected $url;

    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config,$logger);

        $this->url            = $this->apiUrl . '/discussions';
    }

    /**
     * Function to create a discussion
     * @param array $info
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function create($info) {
        $this->log('Creating new discussion');

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {
            $options['json'] = $info;
            $response = $this->http->post($this->url,$options);
            $response = $response->getBody()->getContents();

            return $response;
        } catch (Exception $e) {
            $log = "Error occurred while creating new discussion";
            $this->log($log);
            throw new VanillaGenericException($e->getCode(), $log);
        }
    }

    /**
     * Function to update a discussion
     * @param String $discussionId
     * @param array $info
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function update($discussionId, $info) {
        $this->log('Updating discussion');

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {
            $options['json'] = $info;
            $response = $this->http->patch($this->url.'/'.$discussionId,$options);
            $response = $response->getBody()->getContents();

            return $response;
        } catch (Exception $e) {
            $log = "Error occurred while updating discussion: ".$discussionId;
            $this->log($log." info: ".json_encode($info));
            $this->log("Vanilla discussion update error: ".$e->getMessage());
            throw new VanillaGenericException($e->getCode(), $log);
        }
    }

    /**
     * Function to get discussion details
     * @param String $discussionId
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function getDiscussionDetails($discussionId) {
        $this->log('Getting discussion details');

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {

            $response = $this->http->get($this->url.'/'.$discussionId,$options);
            $response = $response->getBody()->getContents();

            return $response;
        } catch (Exception $e) {
            $log = "Error occurred while getting discussion details: ".$discussionId;
            $this->log("Vanilla discussion get details error: ".$e->getMessage());
            throw new VanillaGenericException($e->getCode(), $log);
        }
    }

    /**
     * Handles on removing discussion on vanilla forum
     *
     * @param $discussionId
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws VanillaGenericException
     */
    public function remove($discussionId) {
        $this->log('Removing discussion: '.$discussionId);

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {
            $response = $this->http->delete($this->url.'/'.$discussionId,$options);
            $response = $response->getBody()->getContents();

            return $response;
        } catch (Exception $e) {
            $log = "Error occurred while deleting discussion: ".$discussionId;
            $this->log($log);
            throw new VanillaGenericException($e->getCode(), $log);
        }
    }
}
