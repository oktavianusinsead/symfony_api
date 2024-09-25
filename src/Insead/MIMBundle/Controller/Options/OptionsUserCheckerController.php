<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsUserCheckerController extends BaseController
{
    /**
     *  CORS settings
     */

    #[Options("/login-checker/{criterion}")]
    public function optionsUserLoginInformationAction() {}

    #[Options("/user-checker/{criterion}")]
    public function optionsUserInformationAction() {}

    #[Options("/get-user-psoft-details/{criterion}")]
    public function optionsGetUserPSoftDetailsAction() {}

    #[Options("/find-user")]
    public function optionsFindUserByUpnAction() {}

    #[Options("/admin/profile/update/{peopleSoftID}/{mode}")]
    public function optionsUpdateUserAvatarAction(){}

    #[Options("/get-person-ad-expiry/{peopleSoftID}")]
    public function optionsGetPersonADExpiry(){}
}
