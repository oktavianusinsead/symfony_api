<?php

namespace esuite\MIMBundle\Controller\Options;

use esuiteSSOBundle\Controller\DefaultController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsSsoValidateController extends DefaultController
{

    #[Options("/sso-validate")]
    public function optionsValidateSsoTokenAction()
    {
    }
}
