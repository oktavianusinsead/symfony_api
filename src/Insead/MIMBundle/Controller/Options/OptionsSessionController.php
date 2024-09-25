<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsSessionController extends BaseController
{
    /**
     *  CORS settings
     */

   #[Options("/sessions")]
    public function optionsSessionsAction() {}

   #[Options("/sessions/{sessionId}")]
    public function optionsSessionAction($id) {}

    #[Options("/sessions/{sessionId}/people")]
    public function optionsSessionPeopleAction($id) {}

    #[Options("/sessions/{sessionId}/people/{peoplesoftId}")]
    public function optionsUnAssignProfessorToSessionAction($sessionId,$peoplesoftId) {}

}
