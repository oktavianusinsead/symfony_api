<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsTemplateSubtaskController extends BaseController
{
    #[options("/template-subtasks")]
    public function optionsTemplateSubtasksAction()
    {
    }

    #[options("/template-subtasks/{templateSubtaskId}")]
    public function optionsTemplateSubtaskAction($templateSubtaskId)
    {
    }
}
