<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsProgrammeLearningJourneyController extends BaseController
{
    #[Options("/programme-learning-journey/{programmeId}")]
    public function optionsProgrammeLearningJourneyAction($id) {}

}
