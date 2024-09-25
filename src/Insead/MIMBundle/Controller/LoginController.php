<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Redis\AuthToken as Redis;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: "Security")]
class LoginController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public LoginManager $loginManager,
                                Redis $redis)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->loginManager->loadServiceManager($redis, $baseParameterBag->get('acl.config'));
    }

    #[Post("/token")]
    public function postRefreshTokenAction(Request $request)
    {
        return $this->loginManager->refreshLogin($request);
    }

    #[Post("/logout")]
    public function postLogoutAction(Request $request)
    {
        return $this->loginManager->deauthenticateLogin($request);
    }

    #[Get("/ssoadmin")]
    public function ssoAdminAction(Request $request)
    {
        $session = $request->getSession();
        $session->set('sso_scope','studyadmin');
        return new RedirectResponse($request->getBaseUrl()."/sso/?sso_scope=studyadmin");
    }

    #[Get("/ssoios")]
    public function ssoIosAction(Request $request)
    {
        $session = $request->getSession();
        $session->set('sso_scope','studyios');
        return new RedirectResponse($request->getBaseUrl()."/sso/?sso_scope=studyios");
    }

    #[Post("/deviceToken/{deviceToken}")]
    public function addIOSDeviceTokenNotificationAction(Request $request, string $deviceToken)
    {
        return $this->loginManager->addToiOSNotification($request, $deviceToken);
    }

    #[Delete("/deviceToken/{deviceToken}")]
    public function removeIOSDeviceTokenNotificationAction(Request $request, string $deviceToken)
    {
        return $this->loginManager->removeToiOSNotification($request, $deviceToken);
    }

    /**
     * @
     * @return mixed
     */
    #[Route('/ssoiostransientkey/token', name: "ios_transient_key", methods: ['GET'])]
    public function iosTransientKeyAction(Request $request){
        $response = new Response(null,200, ['Content-Type' => 'text/html; charset=UTF-8']);
        $response->setContent(
            $this->render(
            '@MIM/iostransientkey.html.twig'
            )
        );

        return $response;

    }

}
