<?php

namespace Insead\MIMLoggingBundle\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Psr\Log\LoggerInterface;

class RequestPayloadListener implements EventSubscriberInterface
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
        $request     = $event->getRequest();
        $method      = $request->getRealMethod();
        $contentType = $request->headers->get('Content-Type');
        $payload     = in_array($method, ['POST', 'PUT']) ? $request->getContent() : null;

        if ($contentType && strlen($contentType) && !str_contains($contentType, 'multipart/form-data')) {
            if ($payload) {
                $payload = preg_replace(
                    '/,"/',
                    ', "',
                    preg_replace(
                        '/"(token)":"[^"]+"/',
                        '"token":******',
                        preg_replace(
                            '/"(password)":"[^"]+"/',
                            '"password":******',
                            $payload
                        )
                    )
                );

                $this->logger->info($payload);
            }
        } else {
            $this->logger->info('[MULTIPART FORM DATA]');
        }

        if ($method !== 'OPTIONS') {
            $this->logger->info('');
        }
    }
}
