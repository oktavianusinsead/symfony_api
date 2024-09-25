<?php
namespace esuiteSSOBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use esuite\MIMBundle\Entity\UserProfileCache;
use esuite\MIMBundle\Entity\UserToken;
use esuite\MIMBundle\Service\Manager\LoginManager;
use esuite\MIMBundle\Service\Redis\AuthToken;
use JetBrains\PhpStorm\ArrayShape;
use LightSaml\Binding\AbstractBinding;
use LightSaml\Binding\BindingFactory;
use LightSaml\Binding\BindingFactoryInterface;
use LightSaml\Build\Container\BuildContainerInterface;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper as LightSamlKey;
use LightSaml\Credential\X509Certificate as LightSamlCertificate;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\Response as LightSamlResponse;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\XmlDSig\Signature;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use LightSaml\State\Sso\SsoSessionState;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\BuildContainer;
use LightSaml\SymfonyBridgeBundle\Bridge\Container\OwnContainer;
use LightSaml\Credential\X509Credential as LightSamlCredential;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LogoutEvent;

use esuite\MIMBundle\Service\Redis\Saml as RedisSaml;

class LogoutSuccessHandler implements EventSubscriberInterface
{
    /**
     * SpLogoutHandler constructor.
     */
    public function __construct(public ManagerRegistry $doctrine,
                                private readonly ParameterBagInterface   $baseParameterBag,
                                private readonly RequestStack            $requestStack,
                                private readonly RedisSaml               $saml,
                                private readonly BuildContainerInterface $buildContainer,
                                private readonly BindingFactoryInterface $bindingFactory,
                                private readonly LoginManager $loginManager,
                                AuthToken $authToken) {
        $this->loginManager->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
    }

    /**
     * @param LogoutEvent $event
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function onLogout(LogoutEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        $bindingFactory = new BindingFactory();
        $bindingType    = $bindingFactory->detectBindingType($request);

        if ($bindingType === null || strlen($bindingType) < 1) {
            // no SAML request: initiate logout
            $this->sendLogoutRequest();
        } else {
            $messageContext = new MessageContext();
            /** @var AbstractBinding $binding */
            $binding = $bindingFactory->create($bindingType);
            $binding->receive($request, $messageContext);
            $samlRequest = $messageContext->getMessage();

            if ($samlRequest instanceof LogoutResponse) {
                // back from IdP after all other SP have been disconnected
                $status = $samlRequest->getStatus();
                $code = $status->getStatusCode() ? $status->getStatusCode()->getValue() : null;

                if ($code === SamlConstants::STATUS_PARTIAL_LOGOUT || $code === SamlConstants::STATUS_SUCCESS) {
                    if (method_exists($samlRequest, 'getSessionIndex')) {
                        $sessionIndex = $samlRequest->getSessionIndex();
                    } else {
                        $sessionIndex = $this->saml->getLogoutTransactionRequest($samlRequest->getInResponseTo());
                    }

                    if (isset($sessionIndex)) {
                        $this->loginManager->deAuthorizeBySessionIndex($sessionIndex);
                    }

                    $this->sendLogoutResponse($samlRequest)->send();
                } elseif ($code === SamlConstants::STATUS_REQUESTER) {
                    $sessionIndex = $this->saml->getLogoutTransactionRequest($samlRequest->getInResponseTo());
                    if ($sessionIndex) {
                        $this->loginManager->deAuthorizeBySessionIndex($sessionIndex);
                    }

                    (new RedirectResponse($this->baseParameterBag->get("idp_logout_landing_page")))->send();
                }
            } elseif ($samlRequest instanceof LogoutRequest) {
                if ($samlRequest->getSessionIndex()) {
                    $this->loginManager->deAuthorizeBySessionIndex($samlRequest->getSessionIndex());
                }

                // logout request from IdP, initiated by another SP
                $this->sendLogoutResponse($samlRequest)->send();
            }
        }
    }

    /**
     * Send a logout request to the IdP
     *
     */
    private function sendLogoutRequest()
    {
        /** @var OwnContainer $own */
        $own = $this->buildContainer->getOwnContainer();
        $ownEntityId    = $own->getOwnEntityDescriptorProvider()->get()->getEntityID();
        $ownCredentials = $own->getOwnCredentials();

        /** @var LightSamlCredential $ownCredential */
        $ownCredential  = $ownCredentials[0];
        $ownSignature = new SignatureWriter($ownCredential->getCertificate(), $ownCredential->getPrivateKey());
        $sessions = $this->buildContainer->getStoreContainer()->getSsoStateStore()->get()->getSsoSessions();

        $nameIDFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';
        $samlLocation = $this->baseParameterBag->get("saml_logout");
        $samlBinding      = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
        $sessionIndex = '';
        $nameID       = '';
        if (count($sessions) < 1) {
            //No valid  saml session on redis
            //get the sessionIndex from UserToken
            //https://edot-api-dev72.esuite.edu/api/v1.2/slologout?transientkey=87079f20783913ae6f635c3173a9a9338cb3578b2953de44891bcf272800c754b830b4ab

            $accessToken = $this->requestStack->getCurrentRequest()->query->get('id');
            if ($accessToken !== null) {
                /** @var UserToken $userToken */
                $userToken = $this->doctrine
                    ->getRepository(UserToken::class)
                    ->findOneBy(['oauth_access_token' => $accessToken]);

                if ($userToken) {
                    $sessionIndex = $userToken->getSessionIndex();
                    /** @var UserProfileCache $userProfileCache */
                    $userProfileCache = $userToken->getUser()->getUserProfileCache();
                    $nameID = $userProfileCache->getUpnEmail();
                } else {
                    $response = new RedirectResponse($this->baseParameterBag->get("idp_logout_landing_page"));
                    $response->send();
                }
            } else {
                $response = new RedirectResponse($this->baseParameterBag->get("idp_logout_landing_page"));
                $response->send();
            }
        } else {
            /** @var SsoSessionState $session */
            $session = $sessions[count($sessions) - 1];

            /** @var EntityDescriptor $idp */
            $idp = $this->buildContainer->getPartyContainer()->getIdpEntityDescriptorStore()->get($session->getIdpEntityId());
            /** @var SingleLogoutService $slo */
            $slo = $idp->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService();
            if (!$slo) {
                $response = new RedirectResponse($this->baseParameterBag->get("idp_logout_landing_page"));
                $response->send();
            }

            $sessionIndex = $session->getSessionIndex(); //_8a462ed2-5ca4-45cf-8735-d9fc91f63377
            $nameID = $session->getNameId();       //jefferson.martin@esuite.edutest
            $nameIDFormat = $session->getNameIdFormat(); //urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified
            $samlLocation = $slo->getLocation();         //https://federation-uat.esuite.edu/adfs/ls/
            $samlBinding = $slo->getBinding();          //urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect
        }

        $transactionID = Helper::generateID();

        $this->saml->setLogoutTransactionRequest($transactionID, $sessionIndex);

        if (strlen($nameID) < 2) return true;

        $logoutRequest = new LogoutRequest();
        $logoutRequest
            ->setSessionIndex($sessionIndex)
            ->setNameID(
                new NameID(
                    $nameID, $nameIDFormat
                )
            )
            ->setDestination($samlLocation)
            ->setID($transactionID)
            ->setIssueInstant(new \DateTime())
            ->setIssuer(new Issuer($ownEntityId))
            ->setSignature($ownSignature);
        $context = new MessageContext();
        $context->setBindingType($samlBinding);
        $context->setMessage($logoutRequest);

        $binding  = $this->bindingFactory->create($samlBinding);

        /** @var Response $response */
        $response = $binding->send($context);
        $response->send();
    }

    /**
     * Send a Success response to a logout request from the IdP
     *
     *
     * @return Response
     * @throws Exception
     */
    private function sendLogoutResponse(SamlMessage $samlRequest) {
        /** @var BuildContainer $builder */
        $builder = $this->buildContainer;
        /** @var OwnContainer $own */
        $own = $builder->getOwnContainer();
        $ownEntityId    = $own->getOwnEntityDescriptorProvider()->get()->getEntityID();
        $ownCredentials = $own->getOwnCredentials();

        /** @var LightSamlCredential $ownCredential */
        $ownCredential  = $ownCredentials[0];
        /** @var Signature $ownSignature */
        $ownSignature = new SignatureWriter($ownCredential->getCertificate(), $ownCredential->getPrivateKey());
        /** @var EntityDescriptor $idp */
        $idp = $builder->getPartyContainer()->getIdpEntityDescriptorStore()->get($samlRequest->getIssuer()->getValue());
        /** @var SingleLogoutService $slo */
        $slo = $idp->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService();

        $logoutResponse = new LogoutResponse();
        $logoutResponse
            ->setStatus(
                new Status(
                    new StatusCode(SamlConstants::STATUS_SUCCESS)
                )
            )
            ->setInResponseTo($samlRequest->getID());
        $logoutResponse
            ->setRelayState($samlRequest->getRelayState())
            ->setDestination($slo->getLocation())
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setIssuer(new Issuer($ownEntityId))
            ->setSignature($ownSignature);

        $context = new MessageContext();
        $context->setBindingType($slo->getBinding());
        $context->setMessage($logoutResponse);

        /** @var BindingFactory $bindingFactory */
        $bindingFactory = $this->bindingFactory;

        /** @var AbstractBinding $binding */
        $binding  = $bindingFactory->create($slo->getBinding());
        return $binding->send($context);
    }

    #[ArrayShape([LogoutEvent::class => "string"])]
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout'
        ];
    }
}
