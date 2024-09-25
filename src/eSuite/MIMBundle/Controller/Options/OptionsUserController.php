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
class OptionsUserController extends BaseController
{

    #[Options("/profile/accept-terms-conditions")]
    public function optionsProfileAcceptTermsConditionsAction()
    {
    }

    #[Options("/profile/groups")]
    public function optionsUserGroupAction()
    {
    }

    #[Options("/profile")]
    public function optionsCurrentUserProfileAction()
    {
    }

    #[Options("/profile/{psoftId}")]
    public function optionsUpdateSpecificUserProfileAction(Request $request, $psoftId) {}

    #[Options("/profile/{psoftId}/{contactType}/{hideStatus}")]
    public function optionsUpdateUserProfileContactStatusAction() {}
}
