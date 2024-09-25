<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsTemplateTaskController extends BaseController
{
    /**
     *  CORS settings
     */

    #[Options("/template-tasks")]
    public function optionsTemplateTasksAction()
    {
    }

    #[Options("/template-tasks/{templateTaskId}")]
    public function optionsTemplateTaskAction($templateTaskId)
    {
    }

    #[Options("/template-tasks/{taskId}")]
    public function optionsTemplateTaskFromTaskAction($taskId)
    {
    }

    #[Options("/template-tasks/{templateTaskId}/create")]
    public function optionsCreateTaskFromTemplateTaskAction($templateTaskId)
    {
    }

    #[Options("/template-tasks/{templateTaskId}/update")]
    public function optionsUpdateTemplateTaskAction($templateTaskId)
    {
    }
}
