<?php

namespace Insead\MIMBundle\Controller\Options;

use Insead\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsVideoController extends BaseController
{
    #[Options("/videos")]
    public function optionsVideosAction() {}

    #[Options("/videos/{videoId}")]
    public function optionsVideoAction($id) {}
}
