<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsProgrammeCompanyLogoController extends BaseController
{
    #[Options("/programme-logo/{programmeId}")]
    public function optionsProgrammeCompanyLogoAction($id) {}

}
