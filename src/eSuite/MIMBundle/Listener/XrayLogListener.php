<?php

namespace esuite\MIMBundle\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Psr\Log\LoggerInterface;

class XrayLogListener implements EventSubscriberInterface
{

    private static $BEARER_HEADER   = 'Bearer';
    private $reqUid = "";
    private $now = "";
    private $startTime = "";

    public function __construct(private readonly LoggerInterface $logger)
    {
        $this->now = new \DateTime();
        $this->startTime = microtime(true);

        //now is need for generateXrayUid, so it should be last
        $this->reqUid = $this->generateXrayUid();
    }

    #[ArrayShape([KernelEvents::REQUEST => "array", KernelEvents::RESPONSE => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST   => ['onKernelRequest', 2], KernelEvents::RESPONSE  => ['onKernelResponse', 10]];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        $logUuid = $this->getLogUuid($request);

        $message = "|"
            . "begin" . "~"
            . microtime(true) . "~"
            . $this->reqUid . "~"
            . $this->startTime . "~"
            . $request->get('_route') . "~"
            . $logUuid . "~"
            . $request->getRealMethod() . "~"
            . $request->getRequestUri() . "~"
            . "|";

        $this->logger->info($message);
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        $logUuid = $this->getLogUuid($request);

        if( $response->getStatusCode() >= 400 && $response->getStatusCode() < 600 ) {
            $message = "|"
                . "end" . "~"
                . microtime(true) . "~"
                . $this->reqUid . "~"
                . $this->startTime . "~"
                . $request->get('_route') . "~"
                . $logUuid . "~"
                . $request->getRealMethod() . "~"
                . $request->getRequestUri() . "~"
                . $response->getStatusCode() . "~"
                . $response->getContent()
                . "|";
        } else {
            $message = "|"
                . "end" . "~"
                . microtime(true) . "~"
                . $this->reqUid . "~"
                . $this->startTime . "~"
                . $request->get('_route') . "~"
                . $logUuid . "~"
                . $request->getRealMethod() . "~"
                . $request->getRequestUri() . "~"
                . $response->getStatusCode() . "~"
                . "|";
        }

        $this->logger->info($message);
    }

    //returns true if $haystack starts with $needle
    private function startsWith($haystack = '', $needle = ''): bool
    {
        if (!$haystack) return false;

        $length = strlen((string) $needle);
        return (substr((string) $haystack, 0, $length) === $needle);
    }

    private function getLogUuid(Request $request)
    {
        //Get Authorization Header
        $headers = $request->headers;
        $authHeader = $headers->get('Authorization');

        $logUuid = "";

        //Check if Header value starts with 'Bearer'
        if($this->startsWith($authHeader, self::$BEARER_HEADER)) {
            // API request. Check access_token in 'users' table
            $token = trim(substr((string) $authHeader, strlen((string) self::$BEARER_HEADER), strlen((string) $authHeader)));
            $logUuid = substr($token, 0, 8) . "..." . substr($token, -8);
        }

        return $logUuid;
    }

    private function generateXrayUid()
    {
        $traceId = "";

        if( $this->now ) {
            if (dechex($this->now->getTimestamp()) && $this->getRandomHex(12)) {
                $traceId = "1-" . dechex($this->now->getTimestamp()) . "-" . $this->getRandomHex(12);
            }
        }

        return $traceId;
    }

    private function getRandomHex($num_bytes=4) {
        return bin2hex(openssl_random_pseudo_bytes($num_bytes));
    }

}
