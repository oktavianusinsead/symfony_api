<?php

namespace Insead\MIMBundle\Controller\Options;

use Insead\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsSessionHandoutsController extends BaseController
{

    #[Options("/session-handouts/{sessionId}")]
    public function optionsLatestSessionHandoutAction($sessionId) {}
}
