<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Symfony\Component\HttpFoundation\Request;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsGroupController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/groups")]
    public function optionsCreateGroupAction(Request $request) {}

    #[Options("/groups/{groupId}")]
    public function optionsGetGroupAction(Request $request, $groupId) {}

    #[Options("/groups/{groupId}/people")]
    public function optionsSessionAssignPersonAction($groupId) {}

    #[Options("/groups/{groupId}/people/{peoplesoftId}")]
    public function optionsSessionUnassignPersonAction($groupId,$peoplesoftId) {}
}
