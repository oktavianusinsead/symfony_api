<?php

namespace esuite\MIMBundle\Listener;

use esuite\MIMBundle\Entity\UserToken;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use esuite\MIMBundle\Service\Redis\AuthToken as Redis;

use esuite\MIMBundle\Exception\SessionTimeoutException;
use esuite\MIMBundle\Exception\PermissionDeniedException;


use Psr\Log\LoggerInterface;

class AuthTokenListener implements EventSubscriberInterface
{
    private $session;

    private static $BEARER_HEADER   = 'Bearer';

    public function __construct(private readonly EntityManager $entityManager, RequestStack $request, private AuthorizationCheckerInterface $security, private readonly LoggerInterface $logger, private readonly Redis $redis, private $secret)
    {
        $this->session          = $request->getSession();
    }

    #[ArrayShape([KernelEvents::REQUEST => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST  => ['onRequest', 1]];
    }

    public function onRequest(RequestEvent $event)
    {
        //Validate token

        //Get Authorization Header
        $headers = $event->getRequest()->headers;
        $authHeader = $headers->get('Authorization');

        //Check if Header value starts with 'Bearer'
        if($this->startsWith($authHeader, self::$BEARER_HEADER)) {
            // API request. Check access_token in 'users' table
            $token = trim(substr((string) $authHeader, strlen((string) self::$BEARER_HEADER), strlen((string) $authHeader)));
            $logUuid = "[" . substr($token, 0, 8) . "..." . substr($token, -8) . "]";

            $now = new \DateTime();

            $interval = new \DateInterval('PT1H');
            $cacheTokenUntil = new \DateTime();
            $cacheTokenUntil->add($interval);

            $cachedToken = $this->redis->getUserInfo($token);

            if( $cachedToken ) {
                try {
                    $this->getDatabaseUserToken($logUuid, $token);
                } catch (PermissionDeniedException) {
                    throw new PermissionDeniedException();
                } catch (SessionTimeoutException) {
                    throw new SessionTimeoutException();
                }
                $loggedInUserToken = json_decode($cachedToken,true);

                $session = $this->session;
                $session->set('scope', $loggedInUserToken['scope']);
                $session->set('user_psoftid', $loggedInUserToken['user_psoftid']);
                $session->set('user_id', $loggedInUserToken['user_id']);
                $session->save();

            } else {

                try {
                    /** @var UserToken $loggedInUserToken */
                    $loggedInUserToken = $this->getDatabaseUserToken($logUuid, $token);
                } catch (PermissionDeniedException) {
                    throw new PermissionDeniedException();
                } catch (SessionTimeoutException) {
                    throw new SessionTimeoutException();
                }

                //cache the token in redis if the token would not expire in the next hour
                if( $cacheTokenUntil->getTimestamp() < $loggedInUserToken->getTokenExpiry()->getTimestamp() ) {
                    $asToken = $this->redis->encrypt(
                        $this->secret . $loggedInUserToken->getUser()->getPeoplesoftId(), //key
                        $now->format("YmdHis")."00", //iv
                        json_encode($loggedInUserToken->getAccessToken()) //data
                    );

                    $tokenObj = ["id" => $loggedInUserToken->getId(), "scope" => $loggedInUserToken->getScope(), "user_psoftid" => $loggedInUserToken->getUser()->getPeoplesoftId(), "user_id" => $loggedInUserToken->getUser()->getId(), "as_token" => $asToken];

                    $this->redis->setLogUuid($logUuid);
                    $this->redis->setUserInfo($token, json_encode($tokenObj));
                }

                $session = $this->session;
                //Set User's scope in Session
                $session->set('scope', $loggedInUserToken->getScope());
                //Set User's Peoplesoft Id in Session
                $session->set('user_psoftid', $loggedInUserToken->getUser()->getPeoplesoftId());
                //Set User's id in Session
                $session->set('user_id', $loggedInUserToken->getUser()->getId());
                $session->save();
                // Allow request to be processed by controllers
            }

                return;
        } else {

            $authChecker = $this->security;
            if ($authChecker->isGranted('PUBLIC_ACCESS')) {
                return;
            } else{
                throw new SessionTimeoutException();
            }

        }
    }

    private function getDatabaseUserToken($logUuid, $cleanedHeaderToken){
        $now = new \DateTime();

        /** @var UserToken $loggedInUserToken */
        $loggedInUserToken = $this->entityManager->getRepository(UserToken::class)->findOneBy(['oauth_access_token' => $cleanedHeaderToken]);

        //If user with access_token not found, throw exception
        if (!$loggedInUserToken) {
            $this->logger->info($logUuid . " " . "AuthTokenListener:: Token was not found in the database.");
            throw new SessionTimeoutException();
        }

        // Check if Tokens have expired
        if ($now->getTimestamp() > $loggedInUserToken->getTokenExpiry()->getTimestamp()) {
            $this->logger->info($logUuid . " " . 'AuthTokenListener::edot@esuite TOKEN EXPIRED AT ' . $loggedInUserToken->getTokenExpiry()->format('Y-m-d H:i:s'));

            // trigger a 401 response after which the client must re-authenticate
            throw new PermissionDeniedException();
        }

        return $loggedInUserToken;
    }

    //returns true if $haystack starts with $needle
    private function startsWith($haystack, $needle): bool
    {
        if (!$haystack) return false;

        $length = strlen((string) $needle);
        return (substr((string) $haystack, 0, $length) === $needle);
    }

}
