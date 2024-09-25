<?php

namespace esuite\MIMBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class ResourceNotFoundException extends \Exception implements MIMExceptionInterface
{

  /**
   * @var string
   *
   */
  public $JSON_ERROR_MESSAGE;

    public function __construct($message = "resource not found")
    {
        parent::__construct($message, 0, null);
        $this->JSON_ERROR_MESSAGE = json_encode(["error" => "resource not found", "message" => $message]);
    }

    public function createResponse()
    {
        $response = new Response();
        $response->setContent($this->JSON_ERROR_MESSAGE);
        $response->setStatusCode(404);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
