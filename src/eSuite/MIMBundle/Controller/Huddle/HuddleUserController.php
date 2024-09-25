<?php

namespace esuite\MIMBundle\Controller\Huddle;

use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Redis\Vanilla as RedisVanilla;
use esuite\MIMBundle\Service\Vanilla\Role;
use esuite\MIMBundle\Service\Vanilla\User as VanillaUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Options;
use FOS\RestBundle\Controller\Annotations\Get;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\HuddleUserManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Vanilla Forums")]
class HuddleUserController extends BaseHuddleController
{

    public function __construct(LoggerInterface                    $logger,
                                ManagerRegistry                    $doctrine,
                                ParameterBagInterface              $baseParameterBag,
                                ManagerBase                        $base,
                                private readonly HuddleUserManager $huddleUserManager)
    {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->base = $base;

        $redisVanilla = new RedisVanilla($baseParameterBag, $logger, $baseParameterBag->get('secret'));
        $role = new Role($baseParameterBag->get('vanilla.config'), $logger, $redisVanilla);
        $vanillaUser = new VanillaUser($baseParameterBag->get('vanilla.config'), $logger, $role);
        $this->huddleUserManager->loadServiceManager($vanillaUser);
    }
    #[Get("/huddle/user-names")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update user firstnames and lastnames. This API endpoint is restricted to coordinators only.")]
    public function prepareUserInformationAction(Request $request)
    {
        return $this->huddleUserManager->prepareUserInfo($request);
    }


}
