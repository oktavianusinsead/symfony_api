<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Symfony\Component\HttpFoundation\Request;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsTokenController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/token/validate/{peopleSoftId}")]
    public function optionsCreateActivityAction(Request $request, $peopleSoftId) {}
}
