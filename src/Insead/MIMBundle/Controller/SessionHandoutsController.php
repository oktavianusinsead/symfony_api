<?php

namespace Insead\MIMBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Put;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\SessionHandoutManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Session")]
class SessionHandoutsController extends BaseController
{
    #[Get("/session-handouts/{sessionId}")]
    #[Allow(["scope" => "studyadmin,studysuper,studystudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve latest session handout")]
    public function getLatestSessionHandoutAction(Request $request, $sessionId, SessionHandoutManager $sessionHandoutManager)
    {
        $this->setLogUuid($request);

        return $sessionHandoutManager->getLatestSessionHandout($request, $sessionId);
    }
}
