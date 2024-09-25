<?php

namespace esuite\MIMBundle\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Response;

use Psr\Log\LoggerInterface;

class CorsListener implements EventSubscriberInterface
{

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[ArrayShape([KernelEvents::REQUEST => "array", KernelEvents::RESPONSE => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST   => ['onKernelRequest', 3], KernelEvents::RESPONSE  => ['onKernelResponse', 10]];
    }

    public function onKernelRequest(RequestEvent $event)
    {

        $this->logger->debug('METHOD::'.$event->getRequest()->getRealMethod());
        // If method is not "OPTIONS", then let request reach controller
        if ($event->getRequest()->getRealMethod() == 'OPTIONS') {
            $response = new Response();
            $response->setStatusCode(200);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Credentials', 'false');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, PUT, GET, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, User-Agent, Cache-Control, CF-Access-Client-Id, CF-Access-Client-Secret');
            $response->headers->set('Access-Control-Max-Age', '2592000');
            $response->headers->set('Content-Type', 'text/plain charset=utf-8');
            $response->headers->set('Content-Length', '0');

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $event->getResponse()->headers->set('Access-Control-Allow-Origin', '*');
        $event->getResponse()->headers->set('Access-Control-Allow-Credentials', 'false');
        $event->getResponse()->headers->set('Access-Control-Allow-Methods', 'POST, PUT, GET, DELETE, OPTIONS');
        $event->getResponse()->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, User-Agent, Cache-Control, CF-Access-Client-Id, CF-Access-Client-Secret');
    }
}
