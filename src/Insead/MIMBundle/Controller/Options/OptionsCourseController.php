<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Symfony\Component\HttpFoundation\Request;
use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsCourseController extends BaseController
{
    /**
     *  CORS settings
     */
    public function optionsCoursesAction()
    {
    }

    public function optionsCourseAction($id)
    {
    }

    public function optionsCourseModulesAction($id)
    {
    }

    public function optionsCourseTasksAction($id)
    {
    }

    public function optionsCourseAnnouncementsAction($id)
    {
    }

    public function optionsCoursePeopleAction($id){}

    #[Options("/courses/{courseId}/people/{peoplesoftId}")]
    public function optionsGetUserInfoInCourseAction($courseId, $peoplesoftId){}

    #[Options("/courses/{courseId}/people")]
    public function optionsAssignUserToCourseAction(Request $request, $courseId){}

    #[Options("/courses/{courseId}/backup")]
    public function optionsCourseBackupAction($courseId){}

    #[Options("/courses/{courseId}/backup-email")]
    public function optionsEmailNotifyCourseBackupAction($courseId){}

    #[Options("/courses")]
    public function optionsListCourseAction(){}

    #[Options("/courses/{courseId}")]
    public function optionsGetCoursesAction(Request $request, $courseId){}

    #[Options("/courses/{courseId}/tasks")]
    public function optionsGetBelongingTasksAction(Request $request, $courseId) {}

    #[Options("/courses/{courseId}/announcements")]
    public function getBelongingAnnouncementsAction(Request $request, $courseId) {}

    #[Options("/courses/{courseId}/timezoneUpdate")]
    public function optionsUpdateCourseTimezoneAction(Request $request, $courseId){}

    #[Options("/courses/{courseId}/timezoneRevert")]
    public function optionsCourseTimezoneRevertAction(Request $request, $courseId){}

    #[Options("/courses/{courseId}/fetchAIPCourseDetails")]
    public function optionsCourseDetailFromAIPAction(Request $request){}

    #[Options("/courses/{courseId}/fetchEnrollmentsFromAIP")]
    public function optionsFetchEnrollmentsFromAIPAction(Request $request){}

    #[Options("/course-backup")]
    public function optionsProcessCoursebackupAction(){}
}
