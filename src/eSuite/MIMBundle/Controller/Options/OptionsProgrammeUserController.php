<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsProgrammeUserController extends BaseController
{
    #[Options("/programme-users/{programmeId}")]
    public function optionsProgrammeUserAction($id) {}

}
