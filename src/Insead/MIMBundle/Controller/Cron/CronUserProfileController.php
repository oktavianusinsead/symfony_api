<?php

namespace Insead\MIMBundle\Controller\Cron;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Entity\UserToken;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Service\AIPService;
use Insead\MIMBundle\Service\Barco\User as BarcoUser;
use Insead\MIMBundle\Service\Barco\User as BarcoUserService;
use Insead\MIMBundle\Service\Barco\UserGroups;
use Insead\MIMBundle\Service\Manager\BarcoManager;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\HuddleUserManager;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Manager\UserCheckerManager;
use Insead\MIMBundle\Service\Manager\UserManager;
use Insead\MIMBundle\Service\Manager\UtilityManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\Redis\Base as RedisMain;
use Insead\MIMBundle\Service\Redis\Vanilla as RedisVanilla;
use Insead\MIMBundle\Service\RestHTTPService;
use Insead\MIMBundle\Service\S3ObjectManager;
use Insead\MIMBundle\Service\Vanilla\Role;
use Insead\MIMBundle\Service\Vanilla\User as VanillaUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Options;
use FOS\RestBundle\Controller\Annotations\Get;
use Insead\MIMBundle\Service\Manager\OrganizationManager;
use Insead\MIMBundle\Service\Manager\UserProfileManager;

class CronUserProfileController extends BaseCronController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                private readonly UserProfileManager $userProfileManager,
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
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->base = $base;

        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $AIPService = new AIPService($logger, $baseParameterBag->get('aip.config'), $restHTTPService);
        $redisVanilla = new RedisVanilla($baseParameterBag, $logger, $baseParameterBag->get('secret'));
        $role = new Role($baseParameterBag->get('vanilla.config'), $logger, $redisVanilla);
        $vanillaUser = new VanillaUser($baseParameterBag->get('vanilla.config'), $logger, $role);
        $user = new BarcoUser($baseParameterBag->get('barco.config'), $logger);
        $userGroups = new UserGroups($baseParameterBag->get('barco.config'), $logger);
        $barcoUserService = new BarcoUserService($baseParameterBag->get('barco.config'), $logger);

        $loginManager->loadServiceManager($redisAuthToken, $this->baseParameterBag->get('acl.config'));
        $huddleUserManager->loadServiceManager($vanillaUser);
        $loginManager->loadServiceManager($redisAuthToken, $this->baseParameterBag->get('acl.config'));
        $userCheckerManager->loadServiceManager($loginManager, $baseParameterBag->get('adws.config'), $baseParameterBag->get('acl.config'), $AIPService, $userProfileManager, $barcoUserService);
        $barcoManager->loadServiceManager($utilityManager, $AIPService, $userProfileManager, $user, $userGroups, $userCheckerManager);

        $this->userProfileManager->loadServiceManager($s3, $baseParameterBag->get('userprofile.config'), $redisMain, $redisAuthToken, $huddleUserManager, $userManager, $loginManager, $organizationManager, $AIPService, $barcoManager);
    }

    /**
     * Handler for ESB to get profile needed to push to PeopleSoft
     */
    #[Get("/aip/bulk/users")]
    public function updatedProfilesAction(Request $request){
        return $this->userProfileManager->profilesUpdated($request);
    }


    /**
     * Handler for ESB to push to profile to Study
     * @return mixed
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/aip/bulk/users")]
    public function receiveProfilesAction(Request $request){
        return $this->userProfileManager->profilesReceive($request);
    }

    /**
     * Handler for ESB to push new organization
     */
    #[Post("/aip/bulk/organizations")]
    public function receiveOrganizationAction(Request $request, OrganizationManager $organizationManager){
        return $organizationManager->receiveOrganization($request);
    }
}
