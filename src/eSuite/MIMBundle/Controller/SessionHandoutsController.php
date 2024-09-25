<?php

namespace esuite\MIMBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Put;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\SessionHandoutManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Session")]
class SessionHandoutsController extends BaseController
{
    #[Get("/session-handouts/{sessionId}")]
    #[Allow(["scope" => "edotadmin,edotsuper,edotstudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve latest session handout")]
    public function getLatestSessionHandoutAction(Request $request, $sessionId, SessionHandoutManager $sessionHandoutManager)
    {
        $this->setLogUuid($request);

        return $sessionHandoutManager->getLatestSessionHandout($request, $sessionId);
    }
}
