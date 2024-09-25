<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
/**
 * Class OptionsCompanionController
 *
 * @package Insead\MIMBundle\Controller
 **/
class OptionsCompanionController extends BaseController
{

    #[Options("/companion/programmes/{peoplesoft_id}")]
    public function optionsCompanionProgrammeAction()
    {
    }

    #[Options("/companion/courses/{peoplesoft_id")]
    public function optionsCompanionCourseAction()
    {
    }
}
