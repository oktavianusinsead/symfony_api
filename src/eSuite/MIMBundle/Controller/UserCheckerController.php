<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\AIPService;
use esuite\MIMBundle\Service\Barco\User as BarcoUser;
use esuite\MIMBundle\Service\Barco\User as BarcoUserService;
use esuite\MIMBundle\Service\Barco\UserGroups;
use esuite\MIMBundle\Service\Manager\BarcoManager;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\HuddleUserManager;
use esuite\MIMBundle\Service\Manager\LoginManager;
use esuite\MIMBundle\Service\Manager\OrganizationManager;
use esuite\MIMBundle\Service\Manager\UserManager;
use esuite\MIMBundle\Service\Manager\UserProfileManager;
use esuite\MIMBundle\Service\Manager\UtilityManager;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\Redis\Base as RedisMain;
use esuite\MIMBundle\Service\RestHTTPService;
use esuite\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\UserCheckerManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserCheckerController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public UserCheckerManager $userCheckerManager,
                                RedisMain $redisMain,
                                AuthToken $redisAuthToken,
                                HuddleUserManager $huddleUserManager,
                                UserManager $userManager,
                                LoginManager $loginManager,
                                OrganizationManager $organizationManager,
                                RestHTTPService $restHTTPService,
                                BarcoManager $barcoManager,
                                UtilityManager $utilityManager,
                                UserProfileManager $userProfileManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $AIPService = new AIPService($logger, $baseParameterBag->get('aip.config'), $restHTTPService);
        $barcoUserService = new BarcoUserService($baseParameterBag->get('barco.config'), $logger);
        $user = new BarcoUser($baseParameterBag->get('barco.config'), $logger);
        $userGroups = new UserGroups($baseParameterBag->get('barco.config'), $logger);

        $loginManager->loadServiceManager($redisAuthToken, $this->baseParameterBag->get('acl.config'));
        $barcoManager->loadServiceManager($utilityManager, $AIPService, $userProfileManager, $user, $userGroups, $userCheckerManager);
        $userProfileManager->loadServiceManager($s3, $baseParameterBag->get('userprofile.config'), $redisMain, $redisAuthToken, $huddleUserManager, $userManager, $loginManager, $organizationManager, $AIPService, $barcoManager);

        $this->userCheckerManager->loadServiceManager($loginManager, $baseParameterBag->get('adws.config'), $baseParameterBag->get('acl.config'), $AIPService, $userProfileManager, $barcoUserService);
    }

    #[Get("/login-checker/{criterion}")]
    #[Allow(["scope" => "edotsuper,edotadmin"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "criterion", description: "Criterion", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to check User information.")]
    public function getUserLoginInformationAction(Request $request, $criterion)
    {
        $request->query->set("agreementOnly",true);

        return $this->userCheckerManager->checkUserInfo( $request, $criterion );
    }

    #[Get("/user-checker/{criterion}")]
    #[Allow(["scope" => "edotsuper,edotsupport,edotadmin"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "criterion", description: "Criterion", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to check User information.")]
    public function getUserInformationAction(Request $request, $criterion)
    {
        return $this->userCheckerManager->checkUserInfo( $request, $criterion );
    }

    #[Get("/get-user-psoft-details/{peopleSoftID}")]
    #[Allow(["scope" => "edotsuper,edotsupport,edotadmin"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peopleSoftID", description: "PeopleSoftID Search criterion", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to check User information.")]
    public function getUserPSoftDetailsAction(Request $request, $peopleSoftID)
    {
        return $this->userCheckerManager->getUserPSoftInfo( $request, $peopleSoftID );
    }

    #[Get("/get-person-ad-expiry/{peopleSoftID}")]
    #[Allow(["scope" => "edotsuper,edotsupport,edotadmin"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peopleSoftID", description: "PeopleSoftID Search criterion", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to check User information.")]
    public function getPersonADExpiry(Request $request, $peopleSoftID)
    {
        return $this->userCheckerManager->getADExpiryDate( $request, $peopleSoftID );
    }

    #[Get("/get-person-info/{peopleSoftID}/{type}")]
    #[Allow(["scope" => "edotsuper,edotsupport,edotadmin"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peopleSoftID", description: "PeopleSoftID Search criterion", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get User information from AIP.")]
    public function getPersonInfo($peopleSoftID, $type)
    {
        return $this->userCheckerManager->getPersonInfo($peopleSoftID, $type);
    }

    #[Post("/find-user")]
    #[Allow(["scope" => "edotsuper,edotadmin,edotsupport"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to find a user based on upn, returns list peoplesoftid.")]
    public function findUserByUpnAction(Request $request)
    {
        $criterion = $request->get("criterion");
        $force = $request->get("force");

        return $this->userCheckerManager->findUsersByUpn( $request, $criterion, $force );
    }

    #[Post("/admin/profile/update/{peopleSoftID}/{mode}")]
    #[Allow(["scope" => "edotsuper,edotadmin"])]
    #[OA\Parameter(name: "peopleSoftID", description: "PeopleSoftID Search criterion", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "mode", description: "mode: [avatar, bio, prefjobTitle]", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update the user's avatar.")]
    public function updateUserAvatarAction(Request $request, $peopleSoftID, $mode)
    {
        return $this->userCheckerManager->adminUpdateUser( $request, $peopleSoftID, $mode );
    }

}
