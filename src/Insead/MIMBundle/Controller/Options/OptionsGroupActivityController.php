<?php

namespace Insead\MIMBundle\Controller\Options;

use Exception;

use Insead\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsGroupActivityController extends BaseController
{
    #[Options("/group-activities")]
    public function optionsGroupActivitiesAction() {}

    #[Options("/group-activities/{id}")]
    public function optionsGroupActivityAction($id) {}
}
