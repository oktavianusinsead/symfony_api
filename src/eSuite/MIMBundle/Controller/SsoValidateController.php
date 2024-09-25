<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\UserProfileCache;
use esuite\MIMBundle\Service\Redis\Saml;
use esuiteSSOBundle\Controller\DefaultController;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use Redis;
use Symfony\Component\HttpFoundation\Session\Session;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Security")]
class SsoValidateController extends DefaultController
{
    /**
     * @throws ResourceNotFoundException
     */
    #[Post("/sso-validate")]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to provide the user info for a given sso-token")]
    public function validateSsoTokenAction(Request $request)
    {
        $randomToken = $request->get("transient-key");

        if( $randomToken ) {
            $dataRaw = $this->redisSaml->getUserTempInfo($randomToken);

            if( !$dataRaw ) {
                $this->log("Could not find the Redis Saml for Transient key: $randomToken");
                throw new ResourceNotFoundException();
            }

            $data = json_decode($dataRaw,true);

            /** @var User $user */
            $user = $this->doctrine
                ->getRepository(User::class)
                ->findOneBy(['peoplesoft_id' => $data['peoplesoft_id']]);

            $this->log("Removing user info for: ".$data['peoplesoft_id']);
            if ($user->getCacheProfile()) {
                /** @var UserProfileCache $cacheProfile */
                $cacheProfile = $user->getCacheProfile();
                $upn = $cacheProfile->getUpnEmail();
                $upnArray = explode(".",$upn);
                array_pop($upnArray); // remove the .edu or .edutest

                $email = implode(".",$upnArray);

                $UPNEmail = $email.".edu";
                $this->log("Removing user info for: ".$UPNEmail);
                $this->redisSaml->removeSAMLUser(strtolower($UPNEmail));

                $environment = $this->getParameterBag()->get('symfony_environment');
                if ($environment === 'dev' || $environment === 'int' || $environment === 'uat') {
                    $testUPNEmailsArray = [
                        $email.".edutest",
                        $email.".org",
                        $email.".orgtest",
                        $email.".test",
                    ];

                    foreach ($testUPNEmailsArray as $testUPNEmail) {
                        $this->log("Removing user info for: " . $testUPNEmail);
                        $this->redisSaml->removeSAMLUser(strtolower($testUPNEmail));
                    }
                }
            }

            $this->redisSaml->removeUserTempInfo($randomToken);

        } else {
            $this->log("Transient-key not found (sso-validate)");
            throw new ResourceNotFoundException();
        }

        return $data;
    }

}
