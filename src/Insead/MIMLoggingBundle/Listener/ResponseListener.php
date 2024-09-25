<?php

namespace Insead\MIMLoggingBundle\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Psr\Log\LoggerInterface;

class ResponseListener implements EventSubscriberInterface
{

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[ArrayShape([KernelEvents::RESPONSE => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onKernelResponse', 9990]];
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getRealMethod() !== 'OPTIONS') {
            $this->logger->info("[" . $response->getStatusCode() . "]");
        }
    }
}
