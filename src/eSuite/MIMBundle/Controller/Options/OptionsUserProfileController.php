<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Symfony\Component\HttpFoundation\Request;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
/**
 * Class OptionsUserController
 *
 * @package esuite\MIMBundle\Controller
 **/
class OptionsUserProfileController extends BaseController
{
    #[Options("/profiles/{id}/avatar")]
    public function optionsBasicProfileAvatarAction(){}

    #[Options("/profiles/{id}")]
    public function optionsUserProfileAction($id){}

    #[Options("/profiles")]
    public function optionsBasicProfilesAction(){}

    #[Options("/profiles/{peopleSoftId}/force/update")]
    public function optionsProfileForceUpdateAction($peopleSoftId){}

    #[Options("profiles/{peoplesoftId}/avatar")]
    public function optionsGetProfilePictureAction(Request $request, $peoplesoftId) {}

    #[Options("/profiles/{peoplesoftId}")]
    public function optionsGetUserProfileAction(Request $request, $peoplesoftId) {}

    #[Options("/aip/bulk/users")]
    public function optionsProfilesAction(){}

    #[Options("/aip/bulk/organizations")]
    public function optionsReceiveOrganizationAction(){}
}
