<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsCountriesController extends BaseController
{

    #[Options("/countries")]
    public function optionsCountriesAction()
    {
    }

    #[Options("/states")]
    public function optionsStatesAction()
    {
    }
}
