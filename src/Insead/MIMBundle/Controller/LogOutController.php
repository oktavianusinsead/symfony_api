<?php

namespace Insead\MIMBundle\Controller;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Security")]
class LogOutController extends BaseController
{
    #[Get("/slologout")]
    public function getLogOutAction(Request $request): RedirectResponse
    {
        if ($request->query->get('transientkey')) {
            if(str_contains($this->baseParameterBag->get('saml_logout'), "dag/saml2")){ // it is duo
                return new RedirectResponse($this->baseParameterBag->get('saml_logout'));
            } else {
                $request->getSession()->set('logoutRequestTransientKey', $request->query->get('transientkey'));
                return new RedirectResponse('/sso/logout?id='.$request->query->get('transientkey'));
            }
        } else {
            return new RedirectResponse($this->baseParameterBag->get('saml_logout'));
        }
    }

}
