<?php
namespace InseadSSOBundle\EventListener;

use LightSaml\Model\Assertion\Attribute;
use LightSaml\Model\Assertion\AttributeStatement;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\Redis\Saml as RedisSaml;

use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\Response as LightSamlResponse;
use LightSaml\Credential\X509Credential as LightSamlCredential;
use LightSaml\Credential\X509Certificate as LightSamlCertificate;
use LightSaml\Credential\KeyHelper as LightSamlKey;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /** @var string */
    protected $returnUrl;

    /**
     * AuthenticationSuccessHandler constructor.
     *
     * @param string $adfsEntityId
     * @param string $adfsSSOPath
     * @param string $certFile
     * @param string $certKey
     */
    public function __construct(RequestStack $request, private readonly LoggerInterface $logger, private readonly RedisSaml $redisSaml, $adfsEntityId , $adfsSSOPath , protected $certFile, protected $certKey)
    {
        $userScope = $request->getCurrentRequest()->get('sso_scope');

        if ($userScope) {
            $session = new Session();
            $session->set('sso_scope', $userScope);
        }

        $this->returnUrl = $request->getCurrentRequest()->getSchemeAndHttpHost().$adfsSSOPath;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $token = $event->getAuthenticationToken();
        $request = $event->getRequest();
        $this->onAuthenticationSuccess($request, $token);
    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $raw = $request->request->get("SAMLResponse");

        $xmlRaw = base64_decode($raw);

        $assertionObj = $this->extractAssertion( $xmlRaw );
        if( $assertionObj->getId() && $assertionObj->getIssuer()->getValue() ) {
            $id         = trim((string) $assertionObj->getId());
            $entityId   = trim((string) $assertionObj->getIssuer()->getValue());

            $idEntryJson = $this->redisSaml->getIdEntry($id);

            $idEntry = json_decode($idEntryJson,true);

            if( isset($idEntry["id"]) && isset($idEntry["entity_id"]) ) {
                if( $idEntry["id"] == $id && $idEntry["entity_id"] == $entityId )  {

                    $this->logger->info("ID Entry found: " . $id);

                    $idEntry["saml_response_raw"] = $raw;

                    $assertionObjAttributes = [];

                    /** @var AttributeStatement $attributeStatement */
                    foreach($assertionObj->getAllAttributeStatements() as $attributeStatement){
                        /** @var Attribute $attribute */
                        foreach($attributeStatement->getAllAttributes() as $attribute){
                            $attributeArray = explode("/",$attribute->getName());
                            $attributeKey = end($attributeArray);
                            $assertionObjAttributes[$attributeKey] = $attribute->getFirstAttributeValue();
                        }
                    }

                    $sessionIndexID = '';
                    if ($assertionObj->hasAnySessionIndex()){
                        foreach ($assertionObj->getAllAuthnStatements() as $authnStatement){
                            if ($authnStatement->getSessionIndex()){
                                $sessionIndexID = $authnStatement->getSessionIndex();
                            }
                        }
                    }

                    $assertionObjAttributes['sessionIndexID'] = $sessionIndexID;

                    $idEntry["saml_response_all_items"] = $assertionObjAttributes;

                    $json = json_encode($idEntry);

                    $this->redisSaml->setIdEntry($id,$json);

                    $token->setAttribute("saml_session_index",$id);

                }
            }
        }

        return new RedirectResponse($this->returnUrl);
    }

    private function extractAssertion( $xmlRaw ) {
        // deserialize XML into a Response data model object
        $deserializationContext = new DeserializationContext();
        $deserializationContext->getDocument()->loadXML($xmlRaw);
        $response = new LightSamlResponse();
        $response->deserialize(
            $deserializationContext->getDocument()->firstChild,
            $deserializationContext
        );

        if( count($response->getAllEncryptedAssertions()) ) {
            // load you key par credential
            $credential = new LightSamlCredential(
                LightSamlCertificate::fromFile($this->certFile),
                LightSamlKey::createPrivateKey($this->certKey, '', true)
            );

            // decrypt the Assertion with your credential
            $decryptDeserializeContext = new DeserializationContext();
            /** @var \LightSaml\Model\Assertion\EncryptedAssertionReader $reader */
            $reader = $response->getFirstEncryptedAssertion();
            $assertion = $reader->decryptMultiAssertion([$credential], $decryptDeserializeContext);
        } else {
            $assertion = $response->getFirstAssertion();
        }

        return $assertion;
    }
}
