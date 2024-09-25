<?php

namespace esuite\MIMLoggingBundle\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Psr\Log\LoggerInterface;

class ResponsePayloadListener implements EventSubscriberInterface
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
        $request     = $event->getRequest();
        $response    = $event->getResponse();
        $contentType = $response->headers->get('CONTENT-TYPE');
        $payload     = $contentType === 'application/json' ? $response->getContent() : null;

        if ($request->getRealMethod() !== 'OPTIONS') {
            if ($payload) {
                $payload = preg_replace(
                    '/,"/',
                    ', "',
                    preg_replace(
                        '/"([a-z]+_token)":"[^"]+"/',
                        '"$1":******',
                        $payload
                    )
                );

                $this->logger->info($payload);
            }

            $this->logger->info("COMPLETED");
        }
    }
}
