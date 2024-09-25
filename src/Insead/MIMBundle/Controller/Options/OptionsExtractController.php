<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Symfony\Component\HttpFoundation\Request;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsExtractController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/extract/programmes")]
    public function optionsGetAllProgrammesAction(Request $request) {}

    #[Options("/extract/courses")]
    public function getAllCoursesAction(Request $request) {}

    #[Options("/extract/sessions")]
    public function optionsGetAllSessionsAction(Request $request) {}

    #[Options("/extract/users")]
    public function optionsGetAllUsersAction(Request $request) {}
    
    #[Options("/extract/admins")]
    public function optionsGetAllAdminsAction(Request $request) {}
    
    #[Options("/extract/non-participants")]
    public function optionsGetNonParticipantsAction(Request $request) {}
    
    #[Options("/extract/composer")]
    public function optionsGetComposerLockAction(Request $request) {}
    
    #[Options("/extract/profile-cache/{peoplesoftId}")]
    public function optionsGetProfileCacheAction(Request $request, $peoplesoftId) {}
    
    #[Options("/extract/organization/{extOrgId}")]
    public function optionsGetOrganizationAction(Request $request, $extOrgId) {}
    
    #[Options("/extract/all")]
    public function optionsGetCustomAllAction(Request $request) {}
}
