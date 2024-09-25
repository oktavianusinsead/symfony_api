<?php

namespace esuite\MIMBundle\Controller\Options;

use Exception;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsGroupSessionController extends BaseController
{
    #[Options("/group-sessions")]
    public function optionsGroupSessionsAction() {}

    #[Options("/group-sessions/{id}")]
    public function optionsGroupSessionAction($id) {}
}
