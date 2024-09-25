<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Exception\ConflictFoundException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Service\AIPService;
use esuite\MIMBundle\Service\Barco\User as BarcoUser;
use esuite\MIMBundle\Service\Barco\User as BarcoUserService;
use esuite\MIMBundle\Service\Barco\UserGroups;
use esuite\MIMBundle\Service\Manager\BarcoManager;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\HuddleUserManager;
use esuite\MIMBundle\Service\Manager\LoginManager;
use esuite\MIMBundle\Service\Manager\OrganizationManager;
use esuite\MIMBundle\Service\Manager\UserCheckerManager;
use esuite\MIMBundle\Service\Manager\UserManager;
use esuite\MIMBundle\Service\Manager\UserProfileManager;
use esuite\MIMBundle\Service\Manager\UtilityManager;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\Redis\Base as RedisMain;
use esuite\MIMBundle\Service\Redis\Vanilla as RedisVanilla;
use esuite\MIMBundle\Service\RestHTTPService;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\Vanilla\Role;
use esuite\MIMBundle\Service\Vanilla\User as VanillaUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\CoordinatorManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Coordinator")]
class CoordinatorController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public CoordinatorManager $coordinatorManager,
                                RestHTTPService $restHTTPService,
                                RedisMain $redisMain,
                                AuthToken $redisAuthToken,
                                UtilityManager $utilityManager,
                                OrganizationManager $organizationManager,
                                UserManager $userManager,
                                BarcoManager $barcoManager,
                                HuddleUserManager $huddleUserManager,
                                LoginManager $loginManager,
                                UserCheckerManager $userCheckerManager,
                                UserProfileManager $userProfileManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $AIPService = new AIPService($logger, $baseParameterBag->get('aip.config'), $restHTTPService);
        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);

        $redisVanilla = new RedisVanilla($baseParameterBag, $logger, $baseParameterBag->get('secret'));
        $role = new Role($baseParameterBag->get('vanilla.config'), $logger, $redisVanilla);
        $vanillaUser = new VanillaUser($baseParameterBag->get('vanilla.config'), $logger, $role);

        $user = new BarcoUser($baseParameterBag->get('barco.config'), $logger);
        $userGroups = new UserGroups($baseParameterBag->get('barco.config'), $logger);
        $barcoUserService = new BarcoUserService($baseParameterBag->get('barco.config'), $logger);

        $huddleUserManager->loadServiceManager($vanillaUser);
        $loginManager->loadServiceManager($redisAuthToken, $this->baseParameterBag->get('acl.config'));
        $userProfileManager->loadServiceManager($s3, $baseParameterBag->get('userprofile.config'), $redisMain, $redisAuthToken, $huddleUserManager, $userManager, $loginManager, $organizationManager, $AIPService, $barcoManager);
        $userCheckerManager->loadServiceManager($loginManager, $baseParameterBag->get('adws.config'), $baseParameterBag->get('acl.config'), $AIPService, $userProfileManager, $barcoUserService);
        $barcoManager->loadServiceManager($utilityManager, $AIPService, $userProfileManager, $user, $userGroups, $userCheckerManager);

        $this->coordinatorManager->loadServiceManager($baseParameterBag->get('acl.config'), $AIPService, $userProfileManager);
    }

    #[Get("/coordinators")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get list of coordinators/administrators")]
    public function getAllCoordinatorsAction(Request $request)
    {
        return $this->coordinatorManager->getCoordinators( $request );
    }

    #[Post("/coordinators")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to add new coordinators/administrators")]
    public function addCoordinatorsAction(Request $request, CoordinatorManager $coordinatorManager)
    {
        return $coordinatorManager->addCoordinators( $request );
    }

    #[Post("/coordinators/addtoadmin")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to add new coordinators/administrators for maanual execution only")]
    public function addToAdminsAction(Request $request, CoordinatorManager $coordinatorManager)
    {
        return $coordinatorManager->addUserToAdmin( $request );
    }

    #[Get("/coordinators/{peoplesoftId}")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peoplesoftId", description: "peoplesoftid of the administrator", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get an coordinator/administrator")]
    public function getCoordinatorAction(Request $request, $peoplesoftId, CoordinatorManager $coordinatorManager)
    {
        return $coordinatorManager->getCoordinator( $request, $peoplesoftId );
    }

    #[Post("/coordinators/{peoplesoftId}")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peoplesoftId", description: "peoplesoftid of the administrator", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to set the blocked flag for the coordinator/administrator")]
    public function updateCoordinatorAction(Request $request, $peoplesoftId, CoordinatorManager $coordinatorManager)
    {
        return $coordinatorManager->updateCoordinator( $request, $peoplesoftId );
    }

}
