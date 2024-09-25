<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsCalendarController extends BaseController
{
    #[Options("/calendars")]
    public function optionsCalendarAction() {}

    #[Options("/calendars/{programmeId}")]
    public function optionsGetCalendarAction($programmeId) {}

    #[Options("/calendars/{programmeId}/data")]
    public function optionsGetCalendarLinksAction($programmeId) {}
}
