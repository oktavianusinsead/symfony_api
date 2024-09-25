<?php

namespace Insead\MIMBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\CompanionManager;
use OpenApi\Attributes as OA;
class CompanionController extends BaseController
{
    #[Get("/companion/programmes/{peoplesoft_id}")]
    #[OA\Response(
        response: 200,
        description: "Handler to get list of programme for a psoftid")]
    public function companionProgrammeAction($peoplesoft_id, CompanionManager $companionManager)
    {
        return $companionManager->programmes($peoplesoft_id);
    }

    #[Get("/companion/courses/{peoplesoft_id}")]
    #[OA\Response(
        response: 200,
        description: "Handler to get list of course for a psoftid")]
    public function companionCourseAction($peoplesoft_id, CompanionManager $companionManager)
    {
        return $companionManager->courses($peoplesoft_id);
    }
}
