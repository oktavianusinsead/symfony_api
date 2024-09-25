<?php

namespace Insead\MIMBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class InvalidResourceException extends \Exception implements MIMExceptionInterface
{

    /**
     * @var string
     *
     */
    public $JSON_ERROR_MESSAGE;

    public function __construct($messages)
    {
        $errors = ['errors' => $messages];

        parent::__construct('Invalid Response', 0, null);

        $this->JSON_ERROR_MESSAGE = json_encode($errors);
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
