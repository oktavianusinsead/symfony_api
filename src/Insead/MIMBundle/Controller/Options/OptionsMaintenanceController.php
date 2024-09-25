<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsMaintenanceController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/maintenances")]
    public function optionsGetMaintenanceAction(){}

    #[Options("/maintenances/{id}")]
    public function optionsUpdateMaintenanceAction(){}


}
