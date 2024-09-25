<?php

namespace esuite\MIMBundle\Controller;

use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use esuite\MIMBundle\Entity\Group;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\UserToken;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ForbiddenException;
use esuite\MIMBundle\Attributes\Allow;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Regex;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\Manager\Base;
use esuite\MIMBundle\Service\Manager\UserProfileManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use esuite\MIMBundle\Service\Manager\BarcoManager;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Redis\Base as RedisMain;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\Manager\HuddleUserManager;
use esuite\MIMBundle\Service\Manager\UserManager;
use esuite\MIMBundle\Service\Manager\LoginManager;
use esuite\MIMBundle\Service\Manager\OrganizationManager;
use esuite\MIMBundle\Service\RestHTTPService;
use esuite\MIMBundle\Service\Manager\UtilityManager;
use esuite\MIMBundle\Service\Manager\UserCheckerManager;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\Redis\Vanilla as RedisVanilla;
use esuite\MIMBundle\Service\AIPService;
use esuite\MIMBundle\Service\Vanilla\Role;
use esuite\MIMBundle\Service\Vanilla\User as VanillaUser;
use esuite\MIMBundle\Service\Barco\User as BarcoUser;
use esuite\MIMBundle\Service\Barco\User as BarcoUserService;
use esuite\MIMBundle\Service\Barco\UserGroups;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserController extends BaseController
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

    /**
     *  User Table
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "User";

    //------- Profile ------//
    #[Post("/profile/accept-terms-conditions")]
    #[Allow(["scope" => "mimstudent,edotstudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update User acceptance of Terms and Conditions.")]
    public function updateAgreementAction(Request $request)
    {
        $this->setLogUuid($request);

        $currentPsoftId = $this->getCurrentUserPsoftId($request);
        /** @var User $user */
        $user = $this->findBy(self::$ENTITY_NAME, ['peoplesoft_id' => $currentPsoftId])[0];
        $user->setAgreement(true);
        $user->setAgreementDate(new \DateTime());
        return $this->update(self::$ENTITY_NAME, $user);
    }

    #[Get("/profile/groups")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Retrieve Group information for the currently authenticated user.")]
    public function getUserGroupAction(Request $request)
    {
        $this->setLogUuid($request);

        // Get Logged in user object
        $user = $this->getCurrentUserObj($request);

        $this->log('Getting Group Info for user: ' . $user->getPeoplesoftId());

        // Get Groups the logged-in user is assigned to
        $em    = $this->doctrine->getManager();
        /** @var Query $query */
        $query = $em->createQuery(
            'SELECT g FROM esuite\MIMBundle\Entity\Group g
                            JOIN g.group_members gm
                            WHERE gm.id = :user_id'
        )->setParameter('user_id', $user->getId());

        $groups = $query->getResult();
        $this->log('GROUPS FOUND: ' . count($groups));

        if(($groups != NULL) && (count($groups) > 0)) {
            $this->log('There are Groups for sure!');

            /** @var Group $group */
            foreach($groups as $group) {
                $this->log('User is associated with Group: ' . $group->getName());
                $group->serializeFullObject(TRUE);
                $group->serializeOnlyPublished(TRUE);
            }
        }

        return ['groups' => $groups];

    }

    #[Post("/profile/{psoftId}")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update profile information  of user with Peoplesoft Id in the URL.")]
    public function updateSpecificUserProfileAction(Request $request, $psoftId)
    {
        $this->setLogUuid($request);

        $this->log('INSIDE updateSpecificUserProfileAction()');

        if($this->getCurrentUserPsoftId($request) === $psoftId) {
            return $this->updateUserProfileAction($request);
        }
        throw new ForbiddenException('User can only update their own profile.');
    }

    #[Post("/profile")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update profile information  of user without Peoplesoft Id in the URL.")]
    public function updateUserProfileAction(Request $request)
    {
        $this->setLogUuid($request);
        return $this->userProfileManager->updateUserProfile($request);
    }

    #[Get("/profile")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Retrieve Full profile information for the currently authenticated user.")]
    public function getCurrentUserProfileAction(Request $request, Base $base)
    {
        $this->setLogUuid($request);

        /** @var User $user */
        $user  = $base->getCurrentUser($request);

        return $this->userProfileManager->getUserProfile($request,$user->getPeoplesoftId());
    }

    #[Post("/profile/{psoftId}/{contactType}/{hideStatus}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "psoftId", description: "PeopleSoftId", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "contactType", description: "ContactType", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "hideStatus", description: "HideStatus", in: "query", schema: new OA\Schema(type: "bool"))]
    #[OA\Response(
        response: 200,
        description: "Handler functions to Update profile to show hide contact from edot admin.")]
    public function updateUserProfileContactStatusAction(Request $request, $psoftId, $contactType, $hideStatus)
    {
        $this->setLogUuid($request);
        return $this->userProfileManager->updateUserProfileContactStatus($request, $psoftId, $contactType, $hideStatus);
    }
}
