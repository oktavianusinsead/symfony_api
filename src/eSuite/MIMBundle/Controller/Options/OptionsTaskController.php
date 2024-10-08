<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsTaskController extends BaseController
{
    #[options("/tasks")]
    public function optionsTasksAction() {}

    #[options("/tasks/{taskId}")]
    public function optionsTaskAction($id) {}
}
