<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\AIPService;
use esuite\MIMBundle\Service\Barco\User;
use esuite\MIMBundle\Service\Barco\User as BarcoUserService;
use esuite\MIMBundle\Service\Barco\UserGroups;
use esuite\MIMBundle\Service\Manager\BarcoManager;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\LoginManager;
use esuite\MIMBundle\Service\Manager\UserCheckerManager;
use esuite\MIMBundle\Service\Manager\UserProfileManager;
use esuite\MIMBundle\Service\Manager\UtilityManager;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\RestHTTPService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use OpenApi\Attributes as OA;

#[OA\Tag("Barco")]
class BarcoUserBaseController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base, public BarcoManager $barcoManager,
                                UtilityManager $utilityManager,
                                UserProfileManager $userProfileManager,
                                UserCheckerManager $userCheckerManager,
                                RestHTTPService $restHTTPService,
                                LoginManager $loginManager,
                                AuthToken $redisAuthToken)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $AIPService = new AIPService($logger, $baseParameterBag->get('aip.config'), $restHTTPService);
        $user = new User($baseParameterBag->get('barco.config'), $logger);
        $userGroups = new UserGroups($baseParameterBag->get('barco.config'), $logger);
        $barcoUserService = new BarcoUserService($baseParameterBag->get('barco.config'), $logger);
        $loginManager->loadServiceManager($redisAuthToken, $this->baseParameterBag->get('acl.config'));

        $userCheckerManager->loadServiceManager($loginManager, $baseParameterBag->get('adws.config'), $baseParameterBag->get('acl.config'), $AIPService, $userProfileManager, $barcoUserService);
        $this->barcoManager->loadServiceManager($utilityManager, $AIPService, $userProfileManager, $user, $userGroups, $userCheckerManager);
    }
}
