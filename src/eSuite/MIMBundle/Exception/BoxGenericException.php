<?php

namespace esuite\MIMBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class BoxGenericException extends \Exception implements MIMExceptionInterface
{

    /**
     * @var string
     *
     */
    public $JSON_ERROR_MESSAGE;

    public function __construct($statusCode = 500, $message = "")
    {
        parent::__construct($message, 0, null);

        $this->JSON_ERROR_MESSAGE = json_encode(
            ["errors" => ["box" => [
                "There was an issue accessing Box. Please try again shortly. -- [$statusCode] - $message"
            ]]]
        );
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
