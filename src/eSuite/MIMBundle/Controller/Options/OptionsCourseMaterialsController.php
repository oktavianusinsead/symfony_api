<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsCourseMaterialsController extends BaseController
{
    #[Options("/course-material/{courseId}/profile-book")]
    public function optionsCourseMaterialBookAction($courseId) {}

    #[Options("/pending-attachments")]
    public function optionsPendingAttachmentsAction(){}
}
