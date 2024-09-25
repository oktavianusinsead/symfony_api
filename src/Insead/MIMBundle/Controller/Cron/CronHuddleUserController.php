<?php

namespace Insead\MIMBundle\Controller\Cron;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\HuddleUserManager;
use Insead\MIMBundle\Service\Redis\Vanilla as RedisVanilla;
use Insead\MIMBundle\Service\Vanilla\Role;
use Insead\MIMBundle\Service\Vanilla\User as VanillaUser;
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
