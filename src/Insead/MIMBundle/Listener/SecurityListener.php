<?php

namespace Insead\MIMBundle\Listener;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityListener implements EventSubscriberInterface
{

    public function __construct(LoggerInterface $logger)
    {
       
    }

    #[ArrayShape([KernelEvents::RESPONSE => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE  => ['onKernelResponse', 10]];
    }
    public function onKernelResponse(ResponseEvent $event)
    {
        $event->getResponse()->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubdomains; preload');
        $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
        $event->getResponse()->headers->set('X-Frame-Options', 'DENY');
        $event->getResponse()->headers->set('X-XSS-Protection', '1; mode=block');
    }
}
