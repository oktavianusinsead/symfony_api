<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsUtilityController extends BaseController
{
    /**
     *  CORS settings
     */

    #[Options("/recyclePeople")]
    public function optionsRecyclePeopleAction(){}

    #[Options("/programme-checklist/{programmeId}")]
    public function optionsProgrammeChecklistAction(){}

    #[Options("/imageToBase64")]
    public function optionsImageToBase64Action() {}
}
