<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Symfony\Component\HttpFoundation\Request;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsActivityController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/activities")]
    public function optionsCreateActivityAction(Request $request) {}

    #[Options("/activities/{activityId}")]
    public function optionsUpdateActivityAction(Request $request, $activityId) {}
}
