<?php

namespace Insead\MIMBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class GenericException extends \Exception implements MIMExceptionInterface
{

    public $JSON_ERROR_MESSAGE;
    
    public function __construct(public $STATUS_CODE = 500, $message = "")
    {
        parent::__construct($message, 0, null);

        $this->JSON_ERROR_MESSAGE = json_encode(["message" => $message] );
    }

    public function createResponse()
    {
        $response = new Response();
        $response->setContent($this->JSON_ERROR_MESSAGE);
        $response->setStatusCode($this->STATUS_CODE);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
