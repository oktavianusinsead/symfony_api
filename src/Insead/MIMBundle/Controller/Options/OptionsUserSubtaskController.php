<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
/**
 * Class OptionsUserController
 *
 * @package Insead\MIMBundle\Controller
 **/
class OptionsUserSubtaskController extends BaseController
{

    #[Options("/profile/completed-subtasks/{id}")]
    public function optionsProfileCompletedSubtaskAction($id)
    {
    }

    #[Options("/profile/completed-subtasks")]
    public function optionsProfileCompletedSubtasksAction()
    {
    }

}
