<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Service\AIPService;
use Insead\MIMBundle\Service\Barco\User;
use Insead\MIMBundle\Service\Barco\User as BarcoUserService;
use Insead\MIMBundle\Service\Barco\UserGroups;
use Insead\MIMBundle\Service\Manager\BarcoManager;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Manager\UserCheckerManager;
use Insead\MIMBundle\Service\Manager\UserProfileManager;
use Insead\MIMBundle\Service\Manager\UtilityManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\RestHTTPService;
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
