<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsSubtaskController extends BaseController
{
    #[Options("/subtasks")]
    public function optionsSubtasksAction() {}

    #[Options("/subtasks/{subtaskId}")]
    public function optionsSubtaskAction($id) {}

    #[Options("/subtasks/{subtaskId}")]
    public function optionsSubtaskDeleteAction($subtaskId) {}
}
