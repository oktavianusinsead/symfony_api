<?php

namespace Insead\MIMBundle\Controller\Options;

use Insead\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsAdminSessionLocationController extends BaseController
{
     #[Options("/admin-session-locations/{courseId}")]
    public function optionsAdminSessionLocationAction($courseId)
    {
    }

}
