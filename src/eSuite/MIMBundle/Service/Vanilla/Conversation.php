<?php

namespace esuite\MIMBundle\Service\Vanilla;

use Psr\Log\LoggerInterface;
use Exception;
use esuite\MIMBundle\Exception\VanillaGenericException;

use esuite\MIMBundle\Service\Vanilla\Role;

class Conversation extends Base
{
    protected $url;

    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config,$logger);

        $this->url            = $this->apiUrl . '/conversations';
    }


    /**
     * Function to create vanilla conversation
     *
     * @param $peopleSoftId
     * @param $userlist
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws VanillaGenericException
     */
    public function create($peopleSoftId,$userlist) {
        $this->log('CREATING VANILLA CONVERSATION ' . $this->env . ' FROM USER: ' . $peopleSoftId . ' LIST: ' . $userlist);

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {
            $options['json'] = ["participantUserIDs" => explode(",",(string) $userlist)];
            $response = $this->http->post($this->url,$options);
            $response = $response->getBody()->getContents();

            return $response;
        } catch (Exception $e) {
            $log = 'Error occurred while creating new conversation for USER: ' . $peopleSoftId . ' LIST: ' . $userlist;
            $this->log($log);
            throw new VanillaGenericException($e->getCode(), $log);
        }
    }

    /**
     * Initiate Leave conversation
     *
     * @param $conversationID
     */
    public function leave($conversationID){
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];

            $this->http->delete($this->url.'/'.$conversationID.'/leave',$options);
            $this->log("The API has leaved the conversation with ID: ".$conversationID);
        } catch (Exception $e) {
            $log = "Error occurred while API is trying to leave the conversation";
            $log.= "\r\n".$e->getMessage();
            $this->log($log);
        }
    }
}
