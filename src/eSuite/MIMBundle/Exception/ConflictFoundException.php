<?php

namespace esuite\MIMBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class ConflictFoundException extends \Exception implements MIMExceptionInterface
{

    /**
     * @var string
     *
     */
    public $JSON_ERROR_MESSAGE;

    public function __construct($message = "request could not be process")
    {
        parent::__construct($message, 0, null);
        $this->JSON_ERROR_MESSAGE = json_encode(["error" => "conflict found", "message" => $message]);
    }

    public function createResponse()
    {
        $response = new Response();
        $response->setContent($this->JSON_ERROR_MESSAGE);
        $response->setStatusCode(409);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
