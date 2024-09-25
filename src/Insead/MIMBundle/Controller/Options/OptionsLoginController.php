<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Symfony\Component\HttpFoundation\Request;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsLoginController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/login")]
    public function optionsAuthenticationAction() {}

 
    #[Options("/token")]
    public function optionsRefreshTokenAction() {}


    #[Options("/logout")]
    public function optionsLogoutAction() {}


    #[Options("/deviceToken/{deviceToken}")]
    public function optionsAddIOSDeviceTokenNotificationAction(Request $request, string $deviceToken) {}


    #[Options("/ssoiostransientkey/token")]
    public function optionsIosTransientKeyAction(Request $request){}
}
