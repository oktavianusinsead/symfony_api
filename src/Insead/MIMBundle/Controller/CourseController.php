<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Service\AIPService;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\CalendarManager;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Manager\ProfileBookManager;
use Insead\MIMBundle\Service\Manager\ProgrammeCompanyLogoManager;
use Insead\MIMBundle\Service\Manager\ProgrammeManager;
use Insead\MIMBundle\Service\Manager\SessionSheetManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\RestHTTPService;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\CourseManager;
use OpenApi\Attributes as OA;
#[OA\Tag(name: "Course")]
class CourseController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                private readonly CourseManager $courseManager,
                                ProfileBookManager $profileBookManager,
                                SessionSheetManager $sessionSheetManager,
                                CalendarManager $calendarManager,
                                RestHTTPService $restHTTPService,
                                LoginManager $login,
                                ProgrammeManager $programmeManager,
                                ProgrammeCompanyLogoManager $programmeCompanyLogoManager,
                                AuthToken $authToken)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $AIPService = new AIPService($logger, $baseParameterBag->get('aip.config'), $restHTTPService);
        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $programmeCompanyLogoManager->loadServiceManager($s3, $baseParameterBag->get('study.s3.config'));
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $profileBookManager->loadServiceManager($s3, $login, $baseParameterBag->get('profilebook.config'));
        $programmeManager->loadServiceManager($s3, $login, $baseParameterBag->get('acl.config'));
        $sessionSheetManager->loadServiceManager($s3, $login, $programmeManager, $programmeCompanyLogoManager, $courseManager);
        $calendarManager->loadServiceManager($s3, $login);
        $this->courseManager->loadServiceManager($profileBookManager, $sessionSheetManager, $calendarManager, $AIPService);
    }

    #[Put("/courses")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a new Course. This API endpoint is restricted to coordinators only")]
    public function createCourseAction(Request $request)
    {
        return $this->courseManager->createCourse($request);
    }

    #[Post("/courses/{courseId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a new Course. This API endpoint is restricted to coordinators only")]
    public function updateCourseAction(Request $request, $courseId)
    {
        return $this->courseManager->updateCourse($request,$courseId);
    }

    #[Get("/courses")]
    #[Allow(["scope" => "mimstudent,studystudent,studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to list all Courses")]
    public function listCourseAction(Request $request)
    {
        return $this->courseManager->getCourses($request);
    }

    #[Get("/courses/{courseId}")]
    #[Allow(["scope" => "studyadmin,studysuper,studyssvc,studysvc"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Course")]
    public function getCoursesAction(Request $request, $courseId)
    {
        return $this->courseManager->getCourse($request,$courseId);
    }

    #[Delete("/courses/{courseId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Course. This API endpoint is restricted to coordinators only")]
    public function deleteCourseAction(Request $request, $courseId)
    {
        return $this->courseManager->deleteCourse($request,$courseId);
    }

    #[Get("/courses/{courseId}/tasks")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to list all Tasks belonging to a Course")]
    public function getBelongingTasksAction(Request $request, $courseId)
    {
        return $this->courseManager->getTasksFromCourse($request,$courseId);
    }

    #[Get("/courses/{courseId}/announcements")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to list Published Announcements belonging to a Course")]
    public function getBelongingAnnouncementsAction(Request $request, $courseId)
    {
        return $this->courseManager->getAnnouncementsFromCourse($request,$courseId);
    }

    #[Post("/courses/{courseId}/timezoneUpdate")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Course Timezone. This API endpoint is restricted to super admin only")]
    public function updateCourseTimezoneAction(Request $request, $courseId)
    {
        return $this->courseManager->updateCourseTimezone($request,$courseId);
    }

    #[Post("/courses/{courseId}/timezoneRevert")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Revert to original of Course Timezone. This API endpoint is restricted to super admin only")]
    public function courseTimezoneRevertAction(Request $request, $courseId)
    {
        return $this->courseManager->revertCourseTimezone($request,$courseId);
    }

    #[Post("/courses/{courseId}/fetchAIPCourseDetails")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to get Course Detail on AIP")]
    public function courseDetailFromAIPAction(Request $request)
    {
        return $this->courseManager->fetchDetailFromAIP($request);
    }

    #[Post("/courses/{courseId}/fetchEnrollmentsFromAIP")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: " Handler function to get Course Detail on AIP")]
    public function fetchEnrollmentsFromAIP(Request $request)
    {
        return $this->courseManager->fetchEnrollmentsFromAIP($request);
    }

    
}
