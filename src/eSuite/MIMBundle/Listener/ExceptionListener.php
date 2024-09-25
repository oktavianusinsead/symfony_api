<?php

namespace esuite\MIMBundle\Listener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

use esuite\MIMBundle\Exception\MIMExceptionInterface;
use esuite\MIMBundle\Exception\OptionsException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;

use Psr\Log\LoggerInterface;
use Twig\Environment;

class ExceptionListener
{
    public function __construct(private readonly LoggerInterface $logger, private readonly Environment $twig)
    {
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $response  = NULL;
        $exception = $event->getThrowable();

        if ($exception instanceof MIMExceptionInterface) {
            $matches = [];
            preg_match('/[^\\\\]+$/', $exception::class, $matches);
            $this->logger->info("MIM Exception: " . $matches[ 0 ]);
            $response = $exception->createResponse();
        } elseif ($exception::class == \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class
                  && $event->getRequest()->getRealMethod() == 'OPTIONS'
        ) {
            $exception = new OptionsException();
            $response  = $exception->createResponse();
        } elseif ($exception::class == \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class) {
            $exception = new ResourceNotFoundException();
            $response  = $exception->createResponse();
        } elseif ($exception::class == \LightSaml\Error\LightSamlContextException::class ) {
            $xmlRaw = base64_decode($event->getRequest()->request->get('SAMLResponse'));
            $startPosAudience = strpos($xmlRaw,'<saml:Audience>') + strlen('<saml:Audience>');
            $endPosAudience = strpos($xmlRaw,'</saml:Audience>');
            $issuer = substr($xmlRaw,$startPosAudience, ($endPosAudience - $startPosAudience));

            $this->logger->info("Current issuer: $issuer");

            $this->logger->error( "SSO EXCEPTION: " . $exception->getMessage() . ' in ' . $exception->getFile() . ' line ' . $exception->getLine() );
            $this->logger->error( "Exception caught by Listener:: \n" . json_encode($exception->getTrace()) );

            $showLogoutButton = false;
            $refreshURL = '/sso';
            if (str_contains($issuer, 'edot_ios')) {
                $refreshURL = '/api/v1.2/ssoios';
            }

            if (str_contains($issuer, 'edot_admin')) {
                $refreshURL = '/api/v1.2/ssoadmin';
            }

            $response = new Response();
            $response->setContent(
                $this->twig->render(
                    '@esuiteSSO/ssowarning.html.twig',
                    ['msg' => "There was a problem processing your Login information. Please try again.", 'webappUrl' => $event->getRequest()->getSchemeAndHttpHost()."/api/v1.2/slologout", 'apiURL' => $event->getRequest()->getSchemeAndHttpHost().$refreshURL, 'showLogoutButton' => $showLogoutButton]
                )
            );

        } else {
            $this->logger->error( "Exception Type - " . "GENERAL EXCEPTION: " . $exception->getMessage() . ' in ' . $exception->getFile() . ' line ' . $exception->getLine() );
            $this->logger->error( "Exception caught by Listener:: \n" . json_encode($exception->getTrace()) );
            $response = new Response();

            $error = [
                "error" => "internal error",
                "message" => "internal error"
            ];

            $response->setContent(json_encode($error));
            $response->setStatusCode(500);
            $response->headers->set('Content-Type', 'application/json');
        }

        $event->setResponse($response);

        return $response;
    }
}
