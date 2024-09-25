<?php

namespace Insead\MIMBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Put;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\CourseMaterialsManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Course")]
class CourseMaterialsController extends BaseController
{
    #[Get("/course-material/{courseId}/profile-book")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "courseId", description: "Course Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "This API endpoint is restricted to coordinators only")]
    public function getCourseMaterialProfileBookAction(Request $request, $courseId, CourseMaterialsManager $courseMaterialsManager)
    {
        return $courseMaterialsManager->getProfileBook($request, $courseId);
    }
}
