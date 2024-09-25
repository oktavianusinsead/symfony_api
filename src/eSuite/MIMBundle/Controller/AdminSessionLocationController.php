<?php

namespace esuite\MIMBundle\Controller;

use esuite\MIMBundle\Exception\ResourceNotFoundException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\AdminSessionLocationManager;
use OpenApi\Attributes as OA;

#[OA\Tag("Admin Session")]
class AdminSessionLocationController extends BaseController
{
    #[Get("/admin-session-locations/{courseId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve the latest value set by the Administrator for Session location")]
    public function getAdminSessionLocationAction(Request $request, $courseId, AdminSessionLocationManager $adminSessionLocationManager)
    {
        return $adminSessionLocationManager->getAdminSessionLocation($request,$courseId);
    }

}
