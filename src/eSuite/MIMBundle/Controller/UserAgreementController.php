<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Post;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\UserAgreementManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserAgreementController extends BaseController
{
    #[Post("/user-agreement/{peoplesoftId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peoplesoftId", description: "PeopleSoftId", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update user agreement.")]
    public function updateUserAgreementAction(Request $request, $peoplesoftId, UserAgreementManager $userAgreementManager)
    {
        return $userAgreementManager->updateUserAgreement($request, $peoplesoftId);
    }
}
