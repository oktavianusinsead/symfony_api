<?php

namespace Insead\MIMLoggingBundle\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Psr\Log\LoggerInterface;

class RequestListener implements EventSubscriberInterface
{

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[ArrayShape([KernelEvents::REQUEST => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST   => ['onKernelRequest', 9990]];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        $this->logger->info("[" . $request->getRealMethod() . "] " . $request->getRequestUri());
    }
}
