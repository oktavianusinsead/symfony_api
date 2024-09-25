<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Entity\ProgrammeUser;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\Administrator;
use esuite\MIMBundle\Entity\VanillaProgrammeGroup;
use esuite\MIMBundle\Entity\VanillaUserGroup;
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
use FOS\RestBundle\Controller\Annotations\Delete;

use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\Group;

use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Exception\BoxGenericException;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\CoursePeopleManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Course")]
class CoursePeopleController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public CoursePeopleManager $coursePeopleManager,
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

        $this->coursePeopleManager->loadServiceManager($baseParameterBag->get('course_people.config'), $AIPService, $userProfileManager);
    }

    #[Get("/courses/{courseId}/people")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "courseId", description: "Course Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to list all students & professors assigned to a Course")]
    public function getAssignedPeopleAction(Request $request, $courseId)
    {
        return $this->coursePeopleManager->getAssignedPeople($request, $courseId);
    }

    #[Post("/courses/{courseId}/people")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "courseId", description: "Course Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to assign a list of users to a course")]
    public function assignUserToCourseAction(Request $request, $courseId)
    {
        return $this->coursePeopleManager->assignUserToCourse($request, $courseId);
    }

    #[Get("/courses/{courseId}/people/{peoplesoftId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "courseId", description: "Course Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "peoplesoftId", description: "PeopleSoftId", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get the current course subscription of a user in a given course")]
    public function getUserInfoInCourseAction(Request $request, $courseId, $peoplesoftId)
    {
        return $this->coursePeopleManager->getUserInfoInCourse($request, $courseId, $peoplesoftId);
    }

    #[Post("/courses/{courseId}/people/{peoplesoftId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "courseId", description: "Course Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "peoplesoftId", description: "PeopleSoftId", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to change a user in a given course")]
    public function changeUserInCourseAction(Request $request, $courseId, $peoplesoftId)
    {
        return $this->coursePeopleManager->changeUserInCourse($request, $courseId, $peoplesoftId);
    }

    #[Delete("/courses/{courseId}/people/{peoplesoftId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "courseId", description: "Course Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "peoplesoftId", description: "PeopleSoftId", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Un-assign a user from a course")]
    public function unAssignUserFromCourseAction(Request $request, $courseId, $peoplesoftId)
    {
        return $this->coursePeopleManager->unAssignUserFromCourse($request, $courseId, $peoplesoftId);
    }
}
