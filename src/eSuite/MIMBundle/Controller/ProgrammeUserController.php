<?php

namespace esuite\MIMBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\ProgrammeUserManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Programme")]
class ProgrammeUserController extends BaseController
{
    #[Get("/programme-users/{programmeId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Get programme core group.")]
    public function getProgrammeUserListAction(Request $request, $programmeId, ProgrammeUserManager $programmeUserManager)
    {
        return $programmeUserManager->getProgrammeCoreGroup($request,$programmeId);
    }

    #[Post("/programme-users/{programmeId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Update programme core group.")]
    public function updateProgrammeUserAction(Request $request, $programmeId, ProgrammeUserManager $programmeUserManager)
    {
        return $programmeUserManager->updateProgrammeCoreGroup($request,$programmeId);
    }
}
