<?php

namespace esuite\MIMBundle\Controller\Options;

use \DateTime;

use esuite\MIMBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsLinkController extends BaseController
{
    #[Options("/links")]
    public function optionsCreateLinkAction(Request $request) {}

    #[Options("/links/{linkId}")]
    public function optionsUpdateLinkAction(Request $request, $linkId) {}
}
