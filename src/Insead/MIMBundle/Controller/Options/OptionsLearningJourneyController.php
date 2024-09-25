<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsLearningJourneyController extends BaseController
{
    #[Options("/learning-journey/{programmeId}")]
    public function optionsProgrammeLearningJourneyAction($id) {}
}
