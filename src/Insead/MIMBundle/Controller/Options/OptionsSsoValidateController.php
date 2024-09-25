<?php

namespace Insead\MIMBundle\Controller\Options;

use InseadSSOBundle\Controller\DefaultController;
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
