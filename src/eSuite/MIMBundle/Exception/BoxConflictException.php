<?php

namespace esuite\MIMBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class BoxConflictException extends \Exception implements MIMExceptionInterface
{

    /**
    * @var string
    *
    */
    public $JSON_ERROR_MESSAGE;

    public function __construct($message = "")
    {
        parent::__construct($message, 0, null);
        $this->JSON_ERROR_MESSAGE = json_encode(["errors" => ["file" => [ "File already exists" ]], "message" => $message]);
    }

    public function createResponse()
    {
        $response = new Response();
        $response->setContent($this->JSON_ERROR_MESSAGE);
        $response->setStatusCode(422);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
