<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Get;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\TokenManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Token")]
class TokenController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                private TokenManager $tokenManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
    }

    /**
     * @throws NotSupported
     */
    #[Get("/token/validate/{peopleSoftId}")]
    #[Allow(["scope" => "studystudent,studyadmin,studysuper,studyssvc,studysvc"])]
    #[OA\Parameter(name: "peopleSoftId ", description: "PeopleSoft Id", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to validate token for a certain peoplesoftid")]
    public function validateTekenAction(Request $request, $peopleSoftId)
    {
        $headers = $request->headers;
        $authHeader = $headers->get('Authorization');

        return $this->tokenManager->validateTokenWithPeopleSoft($authHeader, $peopleSoftId);
    }
}