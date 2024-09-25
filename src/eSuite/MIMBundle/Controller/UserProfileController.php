<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\PermissionDeniedException;
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
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\UserProfileManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserProfileController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public UserProfileManager $userProfileManager,
                                RedisMain $redisMain,
                                AuthToken $redisAuthToken,
                                HuddleUserManager $huddleUserManager,
                                UserManager $userManager,
                                LoginManager $loginManager,
                                OrganizationManager $organizationManager,
                                RestHTTPService $restHTTPService,
                                BarcoManager $barcoManager,
                                UtilityManager $utilityManager,
                                UserCheckerManager $userCheckerManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $AIPService = new AIPService($logger, $baseParameterBag->get('aip.config'), $restHTTPService);

        $redisVanilla = new RedisVanilla($baseParameterBag, $logger, $baseParameterBag->get('secret'));
        $role = new Role($baseParameterBag->get('vanilla.config'), $logger, $redisVanilla);
        $vanillaUser = new VanillaUser($baseParameterBag->get('vanilla.config'), $logger, $role);

        $user = new BarcoUser($baseParameterBag->get('barco.config'), $logger);
        $userGroups = new UserGroups($baseParameterBag->get('barco.config'), $logger);
        $barcoUserService = new BarcoUserService($baseParameterBag->get('barco.config'), $logger);

        $huddleUserManager->loadServiceManager($vanillaUser);
        $loginManager->loadServiceManager($redisAuthToken, $this->baseParameterBag->get('acl.config'));
        $userCheckerManager->loadServiceManager($loginManager, $baseParameterBag->get('adws.config'), $baseParameterBag->get('acl.config'), $AIPService, $userProfileManager, $barcoUserService);
        $barcoManager->loadServiceManager($utilityManager, $AIPService, $userProfileManager, $user, $userGroups, $userCheckerManager);

        $this->userProfileManager->loadServiceManager($s3, $baseParameterBag->get('userprofile.config'), $redisMain, $redisAuthToken, $huddleUserManager, $userManager, $loginManager, $organizationManager, $AIPService, $barcoManager);
    }

    #[Get("profiles/{peoplesoftId}/avatar")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsupport,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peoplesoftId", description: "Peoplesoft Id", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Method to return User profile picture.")]
    public function getProfilePictureAction(Request $request, $peoplesoftId)
    {
        return $this->userProfileManager->getUserAvatar($request,$peoplesoftId);
    }

    #[Get("/profiles/{peoplesoftId}")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peoplesoftId", description: "Peoplesoft Id", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Retrieve Full profile information of user with Peoplesoft Id in the URL.")]
    public function getUserProfileAction(Request $request, $peoplesoftId)
    {
        return $this->userProfileManager->getUserProfile($request,$peoplesoftId);
    }

    #[Get("/profiles")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Retrieve Basic profile information of users with Peoplesoft Ids in the URL.")]
    public function getBasicProfilesAction(Request $request)
    {
        $ids = $request->get('ids');
        return $this->userProfileManager->getBasicProfiles($request,$ids);
    }

    #[GET("/profiles/{peoplesoftId}/force/update")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peoplesoftId", description: "Peoplesoft Id", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Force Full profile update of user with Peoplesoft Id in the URL.")]
    public function userProfileForceUpdateAction(Request $request, $peoplesoftId)
    {
        return [];
    }
}
