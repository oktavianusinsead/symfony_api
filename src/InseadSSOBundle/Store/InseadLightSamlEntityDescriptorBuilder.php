<?php

/*
 * This file is part of the LightSAML-Core package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace InseadSSOBundle\Store;

use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\RoleDescriptor;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Provider\EntityDescriptor\EntityDescriptorProviderInterface;
use LightSaml\SamlConstants;
use LightSaml\Credential\X509Certificate;

use LightSaml\Credential\X509Certificate as LightSamlCertificate;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;


class InseadLightSamlEntityDescriptorBuilder implements EntityDescriptorProviderInterface
{
    /** @var string */
    protected $entityId;

    /** @var string */
    protected $acsUrl;

    /** @var string[] */
    protected $acsBindings;

    /** @var string */
    protected $sloUrl;

    /** @var string[] */
    protected $sloBindings;

    /** @var X509Certificate */
    protected $ownCertificate;

    /** @var EntityDescriptor */
    private $entityDescriptor;

    /**
     * @param string $entityId
     * @param string $acsUrl
     * @param string $sloUrl
     * @param string $ownCertificate
     * @param string[] $acsBindings
     * @param string[] $sloBindings
     * @param string[]|null $use
     */
    public function __construct(
        RequestStack $request,
        $entityId,
        $acsUrl,
        $sloUrl,
        $ownCertificate,
        array $acsBindings = [SamlConstants::BINDING_SAML2_HTTP_POST],
        array $sloBindings = [SamlConstants::BINDING_SAML2_HTTP_POST, SamlConstants::BINDING_SAML2_HTTP_REDIRECT],
        protected $use = [KeyDescriptor::USE_ENCRYPTION, KeyDescriptor::USE_SIGNING]
    ) {

        $session = $request->getSession();
        $userScope = $session->get('sso_scope');
        $entityIdToUsed = $entityId[0];
        if ($userScope){
            $entityIdToUsed = match ($userScope) {
                'studyadmin' => $entityId[1],
                'studyios' => $entityId[2],
                default => $entityId[0],
            };
        }

        $this->entityId = $entityIdToUsed;
        $this->acsUrl = $request->getCurrentRequest()->getSchemeAndHttpHost().$acsUrl;
        $this->sloUrl = $request->getCurrentRequest()->getSchemeAndHttpHost().$sloUrl;
        $this->acsBindings = $acsBindings;
        $this->sloBindings = $sloBindings;

        $this->ownCertificate = LightSamlCertificate::fromFile($ownCertificate);
    }

    /**
     * @return EntityDescriptor
     */
    public function get()
    {
        if (null === $this->entityDescriptor) {
            $this->entityDescriptor = $this->getEntityDescriptor();
            if (false === $this->entityDescriptor instanceof EntityDescriptor) {
                throw new \LogicException('Expected EntityDescriptor');
            }
        }

        return $this->entityDescriptor;
    }

    /**
     * @return EntityDescriptor
     */
    protected function getEntityDescriptor()
    {
        $entityDescriptor = new EntityDescriptor();
        $entityDescriptor->setEntityID($this->entityId);

        $spSsoDescriptor = $this->getSpSsoDescriptor();
        if ($spSsoDescriptor) {
            $entityDescriptor->addItem($spSsoDescriptor);
        }

        return $entityDescriptor;
    }

    /**
     * @return SpSsoDescriptor|null
     */
    protected function getSpSsoDescriptor()
    {
        if (null === $this->acsUrl) {
            return null;
        }

        $spSso = new SpSsoDescriptor();

        foreach ($this->acsBindings as $index => $binding) {
            $acs = new AssertionConsumerService();
            $acs->setIndex($index)->setLocation($this->acsUrl)->setBinding($binding);

            $spSso->addAssertionConsumerService($acs);
        }

        foreach ($this->sloBindings as $index => $binding) {
            $slo = new SingleLogoutService();
            $slo->setLocation($this->sloUrl)->setBinding($binding);

            $spSso->addSingleLogoutService($slo);
        }

        $this->addKeyDescriptors($spSso);

        return $spSso;
    }

    protected function addKeyDescriptors(RoleDescriptor $descriptor)
    {
        if ($this->use) {
            foreach ($this->use as $use) {
                $kd = new KeyDescriptor();
                $kd->setUse($use);
                $kd->setCertificate($this->ownCertificate);

                $descriptor->addKeyDescriptor($kd);
            }
        } else {
            $kd = new KeyDescriptor();
            $kd->setCertificate($this->ownCertificate);

            $descriptor->addKeyDescriptor($kd);
        }
    }
}
