<?php

namespace esuite\MIMBundle\Controller\Cron;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\HuddleUserManager;
use esuite\MIMBundle\Service\Redis\Vanilla as RedisVanilla;
use esuite\MIMBundle\Service\Vanilla\Role;
use esuite\MIMBundle\Service\Vanilla\User as VanillaUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CronHuddleUserController extends BaseCronController
{

    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
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

    #[Post("/cron-huddle-users")]
    public function processCronHuddleUsersAction()
    {
        return $this->huddleUserManager->processHuddleUsers();
    }

}
