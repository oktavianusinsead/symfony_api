<?php

namespace esuite\MIMBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class PermissionDeniedException extends \Exception implements MIMExceptionInterface
{

  /**
   * @var string
   *
   */
  public $JSON_ERROR_MESSAGE;

	public function __construct($message = "Forbidden")
  {
    parent::__construct($message, 0, null);
    $this->JSON_ERROR_MESSAGE = json_encode(["error" => "Forbidden", "message" => $message]);

	}

	public function createResponse()
  {
    $response = new Response();
    $response->setContent($this->JSON_ERROR_MESSAGE);
    $response->setStatusCode(401);
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }
}
