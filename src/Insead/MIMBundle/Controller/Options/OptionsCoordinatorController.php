<?php

namespace Insead\MIMBundle\Controller\Options;

use Insead\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsCoordinatorController extends BaseController
{
    #[Options("/coordinators")]
    public function optionsCoordinatorsAction() {}

    #[Options("/coordinators/{peoplesoftId}")]
    public function optionsGetCoordinatorsAction() {}

}
