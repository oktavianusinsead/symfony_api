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
class OptionsUserAnnouncementController extends BaseController
{
    #[Options("/profile/viewed-announcements/{id}")]
    public function optionsViewedAnnouncementAction($id)
    {
    }

    #[Options("/profile/viewed-announcements")]
    public function optionsViewedAnnouncementsAction()
    {
    }

}
