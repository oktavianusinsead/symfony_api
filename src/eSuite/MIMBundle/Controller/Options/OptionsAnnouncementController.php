<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Symfony\Component\HttpFoundation\Request;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsAnnouncementController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/announcements")]
    public function optionsCreateAnnouncementAction(Request $request) {}

    #[Options("/announcements/{announcementId}")]
    public function optionsUpdateAnnouncementAction(Request $request, $announcementId) {}
}
