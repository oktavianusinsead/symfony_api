<?php

namespace Insead\MIMBundle\Controller;

use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;

use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\AdminSessionLocationManager;
use OpenApi\Attributes as OA;

#[OA\Tag("Admin Session")]
class AdminSessionLocationController extends BaseController
{
    #[Get("/admin-session-locations/{courseId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve the latest value set by the Administrator for Session location")]
    public function getAdminSessionLocationAction(Request $request, $courseId, AdminSessionLocationManager $adminSessionLocationManager)
    {
        return $adminSessionLocationManager->getAdminSessionLocation($request,$courseId);
    }

}
