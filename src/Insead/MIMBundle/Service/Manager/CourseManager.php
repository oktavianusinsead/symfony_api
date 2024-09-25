<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\Query;
use Exception;

use Insead\MIMBundle\Entity\Activity;
use Insead\MIMBundle\Entity\GroupActivity;
use Insead\MIMBundle\Entity\GroupSession;
use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Entity\Session;
use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Entity\UserProfile;
use Insead\MIMBundle\Entity\UserProfileCache;
use Insead\MIMBundle\Exception\ConflictFoundException;
use Insead\MIMBundle\Service\StudyNotify;
use Insead\MIMBundle\Service\AIPService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Group;
use Insead\MIMBundle\Entity\Task;

use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Exception\ForbiddenException;


class CourseManager extends Base
{

    /**
     * @var string
     *
     */
    public static $UID_PREFIX = "C";

    /**
     * @var string
     *
     */
    public static $UID_DELIMITER = "-";

    /**
     * @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Course";


    protected ProfileBookManager $profileBookManager;
    protected SessionSheetManager $sessionSheetManager;
    protected CalendarManager $calendarManager;
    protected AIPService $aipService;

    public function loadServiceManager( ProfileBookManager $profileBookManager, SessionSheetManager $sessionSheetManager, CalendarManager $calendarManager, AIPService $aipService )
    {
        $this->profileBookManager = $profileBookManager;
        $this->sessionSheetManager = $sessionSheetManager;
        $this->calendarManager = $calendarManager;
        $this->aipService = $aipService;
    }
    /**
     * Function to create an Course
     *
     * @param Request       $request            Request Object
     *
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return Response
     */
    public function createCourse(Request $request)
    {
        $this->log("COURSE:" . $request->get('name'));

        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $request->get('programme_id')]);

        $this->checkReadWriteAccessToProgramme($request,$programme);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme not found');
        }

        // Validate Course fields. This is because Symfony's validation runs only when you try to save the model instance
        // It is required to validate before that because this endpoint creates Box group and this needs to vbe validated before save

        if (!( $request->get('name') )) {
            throw new InvalidResourceException(['name' => ['Please enter valid Course Name.']]);
        }

        if (!( $request->get('abbreviation') )) {
            throw new InvalidResourceException(['abbreviation' => ['Please enter valid Course Code.']]);
        }

        if (!( $request->get('start_date') )) {
            throw new InvalidResourceException(['start_date' => ['Please enter valid Start Date.']]);
        }

        $existingSession = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['abbreviation' => $request->get('abbreviation')]);

        if ($existingSession) {
            throw new InvalidResourceException(['abbreviation' => ['This code is already used for another Course. Please enter another unique Course Code.']]);
        }

        $course = new Course();
        $course->setProgramme($programme);
        $course->setAbbreviation($request->get('abbreviation'));
        $course->setUid( $this->createUid( $course->getAbbreviation() ) );

        //process other fields
        $course = $this->processCourse( $request, $course );
        $course->setOriginalCountry($course->getCountry());
        $course->setOriginalTimezone($course->getTimezone());
        $course->setPsLocation($course->getPsLocation());

        // Create box users group
        $course->setBoxGroupId("S3-".mktime(date("H")));
        if($course->getCourseTypeView()==1)
        {
            $courseTypeView=1;
        }
        else{
            $courseTypeView=0;
        }
        $course->setCourseTypeView($courseTypeView);
        try {
            $responseObj = $this->createRecord(self::$ENTITY_NAME, $course);
        } catch(Exception $e) {
            $this->log('Exception occurred while creating a Course. Hence deleting the Box Collaboration');
            throw $e;
        }
        $this->log("Created Course");

        // Create default group for Course
        $group = new Group();
        $group->setCourse($course);
        $group->setName('Everyone');
        $group->setColour(-1);
        if($request->get('start_date')) {
            $group->setStartDate(new \DateTime($request->get('start_date')));
        }
        if($request->get('end_date')) {
            $group->setEndDate(new \DateTime($request->get('end_date')));
        }
        $group->setCourseDefault(TRUE);
        $this->createRecord('Group', $group);

        return $responseObj;
    }

    /**
     * Function to retrieve an existing Course
     *
     * @param Request       $request            Request Object
     * @param String        $courseId        id of the Course
     *
     * @throws ResourceNotFoundException
     * @throws ForbiddenException
     *
     * @return array
     */
    public function getCourse(Request $request, $courseId)
    {
        $this->log("Course:" . $courseId);

        $scope  = $this->getCurrentUserScope($request);
        $userId = $this->getCurrentUserId($request);
        $user   = $this->getCurrentUserObj($request);

        if (
            $scope == "studyadmin"
            || $scope == "studysuper"
            || $scope == "studyssvc"
            || $scope == "studysvc"
        ) {
            $this->log('getting Course for an ADMIN:' . $courseId);

            /** @var Course $course */
            $course = $this->entityManager
                ->getRepository(Course::class)
                ->findOneBy(['id' => $courseId]);

            if(!$course) {
                $this->log('Course not found');
                throw new ResourceNotFoundException('Course not found');
            }

            /** @var Programme $programme */
            $programme = $course->getProgramme();
            if ($programme->getViewType() != 3){
                $course->setCourseTypeView($programme->getViewType()); 

            }
            //check if user has access to the course programme
            $programme->setRequestorId($user->getId());
            $programme->setRequestorScope($scope);

            if( $scope != "studysuper" ) {
                $programme->setIncludeHidden(true);
                if(!$programme->checkIfMy() && $programme->getPrivate()) {
                    $this->log('Course not found');
                    throw new ResourceNotFoundException('Course not found');
                }
            }

            return [strtolower(self::$ENTITY_NAME) => $course];

        } elseif (
            $scope == "mimstudent"
            || $scope == "studystudent"

        ) {
            $this->log('getting Course for an Student:' . $courseId);

            // Check of User is assigned to the course first
            $em    = $this->entityManager;

            /** @var Query $query */
            $query = $em->createQuery(
                'SELECT c FROM Insead\MIMBundle\Entity\Course c
                                JOIN c.courseSubscriptions cs
                                JOIN cs.user u
                                WHERE c.published = :published and u.id = :user_id and c.id = :course_id'
                )
                ->setParameter('published', TRUE)
                ->setParameter('user_id', $userId)
                ->setParameter('course_id', $courseId);

            $course = $query->getResult();
            $programme = $course->getProgramme();
            if ($programme->getViewType()!=3){
                $course->setCourseTypeView($programme->getViewType()); 

            }    
            if (count($course) > 0) {

                /** @var Course $courseItem */
                $courseItem  = $course[ 0 ];
                $courseItem->serializeOnlyPublished(TRUE);

                return [strtolower(self::$ENTITY_NAME) => $course];
            } else {
                throw new ResourceNotFoundException('Invalid course requested.');
            }

        } else {
            throw new ForbiddenException();
        }
    }

    /**
     * Function to retrieve existing Courses
     *
     * @param Request       $request            Request Object
     *
     * @throws ResourceNotFoundException
     * @throws ForbiddenException
     *
     * @return array
     */
    public function getCourses(Request $request)
    {
        $ids = $request->get('ids');
        $courses = [];

        $this->log("Listing Courses");
        $scope  = $this->getCurrentUserScope($request);
        $userId = $this->getCurrentUserId($request);
        $user   = $this->getCurrentUserObj($request);
        $em     = $this->entityManager;

        if (
            $scope == "mimstudent"
            || $scope == "studystudent"
        ) {

            // Get Courses the current user is assigned to as 'student' and are published
            /** @var Query $query */
            $query = $em->createQuery(
                'SELECT c FROM Insead\MIMBundle\Entity\Course c
                                JOIN c.courseSubscriptions cs
                                JOIN cs.user u
                                WHERE c.published = :published and u.id = :user_id'
            )->setParameter('published', TRUE)
                ->setParameter('user_id', $userId);

            $courses = $query->getResult();

            // Serialize only published sub-entities
            foreach ($courses as $course) {
                $programme = $course->getProgramme();
                if ($programme->getViewType()!=3){
                    $course->setCourseTypeView($programme->getViewType()); 

                }
                /** @var Course $course */
                $course->serializeOnlyPublished(TRUE);
            }

            $responseObj = ['courses' => $courses];

        } elseif (
            $scope == "studyadmin"
            || $scope == "studysuper"
        ) {

            foreach($ids as $id)
            {
                $course = $this->entityManager
                    ->getRepository(Course::class)
                    ->findOneBy(['id' => $id]);

                $programme = $course->getProgramme();
                if ($programme->getViewType()!=3){
                    $course->setCourseTypeView($programme->getViewType()); 

                }
                //check if user has access to the programme
                $hasAccess = true;
                $programme->setRequestorId($user->getId());
                $programme->setRequestorScope($scope);

                if( $scope != "studysuper" ) {
                    $programme->setIncludeHidden(true);
                    if( !$programme->checkIfMy() && $programme->getPrivate() ) {
                        $hasAccess = false;
                    }
                }

                if( $hasAccess ) {
                    if( $course ) {
                        array_push($courses, $course);
                    }
                }
            }

            $responseObj = ['courses' => $courses];

        } else {
            throw new ForbiddenException();
        }

        return $responseObj;
    }

    /**
     * Function to update an existing Course
     *
     * @param Request       $request            Request Object
     * @param String        $courseId        id of the Course
     *
     * @throws ResourceNotFoundException
     * @throws ForbiddenException
     *
     * @return Response
     */
    public function updateCourse(Request $request, $courseId)
    {
        $this->checkReadWriteAccess($request,$courseId);

        $resetBackup = FALSE;

        $this->validateRelationshipUpdate('programme_id', $request);
        $this->validateRelationshipUpdate('abbreviation', $request);

        // Find the course
        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $wasPublished = $course->getPublished();

        if( ( ($wasPublished == FALSE) && ($request->get('published') == TRUE) ) || ( ($wasPublished == TRUE) && ($request->get('published') == FALSE) )) {
            $resetBackup = TRUE;
        }

        // Set new values for Course
        $course = $this->processCourse( $request, $course );

        $responseObj = $this->updateRecord(self::$ENTITY_NAME, $course);

        // Update Default Group's timeline
        if($request->get('start_date') || $request->get('end_date')) {
            $defaultGroup = NULL;
            foreach($course->getGroups() as $group) {

                /** @var Group $group */
                if($group->getCourseDefault()) {
                    $defaultGroup = $group;
                    break;
                }
            }

            if($request->get('start_date')) {
                $defaultGroup->setStartDate(new \DateTime($request->get('start_date')));
            }
            if($request->get('end_date')) {
                $defaultGroup->setEndDate(new \DateTime($request->get('end_date')));
            }

            $this->updateRecord('Group', $defaultGroup);
        }

        if($wasPublished || $request->get('published') == TRUE || $request->get('published') == FALSE) {

            // push notifications if published
            $this->notify->setLogUuid($request);
            $this->notify->message($course, self::$ENTITY_NAME);
        }

        if ($resetBackup && ($course->getProgramme()->getPublished() == TRUE)) {
            $this->log("Needs a Backup reset.");
            // Update Course Backup
            $this->backup->updateCoursebackup($course);
        }

        return $responseObj;
    }

    /**
     * Function to delete an existing Course
     *
     * @param Request       $request            Request Object
     * @param String        $courseId        id of the Course
     *
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return Response
     */
    public function deleteCourse(Request $request, $courseId)
    {
        $this->checkReadWriteAccess($request,$courseId);

        $forceDelete = $request->query->has("force");

        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $em = $this->entityManager;

        // Delete Course's default group
        $courseDefaultGroupA = $this->entityManager
            ->getRepository(Group::class)
            ->findBy(['course_default' => TRUE, 'course' => $course->getId()]);

        if(count($courseDefaultGroupA) > 0) {
            // This Course has a default Group
            $courseDefaultGroup = $courseDefaultGroupA[0];

            $em->remove($courseDefaultGroup);

            $responseObj = new Response();
            $responseObj->setStatusCode(204);
        }

        $em->remove($course);
        $em->flush();

        $responseObj = new Response();
        $responseObj->setStatusCode(204);

        // push notification
        $this->notify->setLogUuid($request);
        $this->notify->message($course, self::$ENTITY_NAME);

        return $responseObj;
    }

    /**
     * Function to retrieve tasks belonging to an existing Course
     *
     * @param Request       $request            Request Object
     * @param String        $courseId        id of the Course
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getTasksFromCourse(Request $request, $courseId)
    {
        $this->log("Getting Tasks for Course:" . $courseId);

        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $course->showOnlyTasksWithSubtasks(TRUE);
        $tasks  = $course->getTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                /** @var Task $task */
                $task->serializeFullObject(TRUE);
            }
        }
        $responseObj = ['tasks' => $tasks];

        return $responseObj;
    }

    /**
     * Function to retrieve announcements belonging to an existing Course
     *
     * @param Request       $request            Request Object
     * @param String        $courseId        id of the Course
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getAnnouncementsFromCourse(Request $request, $courseId)
    {
        $this->log("Getting Announcements for Course:" . $courseId);

        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $announcements = $course->getPublishedAnnouncements();

        $responseObj   = ['announcements' => $announcements];

        return $responseObj;
    }

    /**
     * Function to process the Course information
     *
     * @param Request       $request            Request Object
     * @param Course        $course             Course Object
     *
     * @return Course
     */
    private function processCourse($request,$course) {
        if($request->get('name')) {
            $course->setName($request->get('name'));
        }
        if($request->get('start_date')) {
            $course->setStartDate(new \DateTime($request->get('start_date')));
        }
        if($request->get('end_date')) {
            $course->setEndDate(new \DateTime($request->get('end_date')));
        }
        if($request->get('country')) {
            $course->setCountry($request->get('country'));
        }
        if($request->get('timezone')) {
            $course->setTimezone($request->get('timezone'));
        }
        if($request->get('published')) {
            $course->setPublished($request->get('published'));
        } else {
            if( $request->get('published') === false ) {
                $course->setPublished(false);
            }
        }

        if($request->get('ps_crse_id')) {
            $course->setPsCrseId($request->get('ps_crse_id'));
        }
        if($request->get('ps_acad_career')) {
            $course->setPsAcadCareer($request->get('ps_acad_career'));
        }
        if($request->get('ps_strm')) {
            $course->setPsStrm($request->get('ps_strm'));
        }
        if($request->get('ps_session_code')) {
            $course->setPsSessionCode($request->get('ps_session_code'));
        }
        if($request->get('ps_class_section')) {
            $course->setPsClassSection($request->get('ps_class_section'));
        }
        if($request->get('ps_class_nbr')) {
            $course->setPsClassNbr($request->get('ps_class_nbr'));
        }
        if($request->get('ps_class_descr')) {
            $course->setPsClassDescr($request->get('ps_class_descr'));
        }
        if($request->get('course_type_view')) {
            $course->setCourseTypeView($request->get('course_type_view'));
        }

        return $course;
    }

    /**
     * Function to update Course Timezone
     *
     * @param Request       $request            Request Object
     * @param String        $courseId        id of the Course
     *
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return bool
     */
    public function updateCourseTimezone(Request $request, $courseId)
    {
        $this->log("Updating course timezone for course: (".$courseId.")");
        $this->checkReadWriteAccess($request,$courseId);

        if($request->request->has('country')) {
            $country = $request->get('country');
        } else {
            throw new InvalidResourceException(['Country' => ['Country is missing']]);
        }

        if($request->request->has('timezone')) {
            $timezone = $request->get('timezone');
        } else {
            throw new InvalidResourceException(['Timezone' => ['Timezone is missing']]);
        }

        // Find the course
        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if(!$course) {
            $this->log('Course not found');

            throw new ResourceNotFoundException('Course not found');
        }

        $courseCurrentTimezone = $course->getTimezone();
        $adjustedTimezone = 0;

        if ($courseCurrentTimezone !== $timezone){
            $adjustedTimezone = (int)$courseCurrentTimezone - ((int)$timezone);
        }

        if ($adjustedTimezone !== 0) {

            /**
             * Adjust session timings
             */
            $sessions = $course->getSessions();
            /** @var Session $session */
            foreach ($sessions as $session) {
                // Check if this Group has Published Scheduled Sessions
                if (count($session->getGroupSessions()) > 0) {
                    /** @var GroupSession $groupSession */
                    foreach ($session->getGroupSessions() as $groupSession) {

                        $adjustedStartDate = (new \DateTime($groupSession->getStartDate()->format("Y-m-d H:i:s")))->modify($adjustedTimezone.' hours');
                        $adjustedEndDate = (new \DateTime($groupSession->getEndDate()->format("Y-m-d H:i:s")))->modify($adjustedTimezone.' hours');

                        $groupSession->setStartDate($adjustedStartDate);
                        $groupSession->setEndDate($adjustedEndDate);
                    }

                    $this->entityManager->persist($groupSession);
                }
            }

            /**
             * Adjust activity timings
             */
            $activities = $course->getActivities();
            /** @var Activity $activity */
            foreach ($activities as $activity) {
                // Check if this Group has Published Scheduled Sessions
                if (count($activity->getGroupActivities()) > 0) {
                    /** @var GroupActivity $groupActivity */
                    foreach ($activity->getGroupActivities() as $groupActivity) {

                        $adjustedStartDate = (new \DateTime($groupActivity->getStartDate()->format("Y-m-d H:i:s")))->modify($adjustedTimezone.' hours');
                        $adjustedEndDate = (new \DateTime($groupActivity->getEndDate()->format("Y-m-d H:i:s")))->modify($adjustedTimezone.' hours');

                        $groupActivity->setStartDate($adjustedStartDate);
                        $groupActivity->setEndDate($adjustedEndDate);
                    }

                    $this->entityManager->persist($groupActivity);
                }
            }

            $course->setCountry($country);
            $course->setTimezone($timezone);

            if ($this->updateRecord(self::$ENTITY_NAME, $course)) {
                $this->updateCourseMaterials( $request, $course );
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Function to update Course Timezone
     *
     * @param Request       $request            Request Object
     * @param String        $courseId        id of the Course
     *
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return bool
     */
    public function revertCourseTimezone(Request $request, $courseId)
    {
        $this->log("Reverting course timezone for course: (".$courseId.")");
        $this->checkReadWriteAccess($request,$courseId);

        // Find the course
        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $sessions = $course->getSessions();
        /** @var Session $session */
        foreach ($sessions as $session) {
            // Check if this Group has Published Scheduled Sessions
            if (count($session->getGroupSessions()) > 0) {
                /** @var GroupSession $groupSession */
                foreach ($session->getGroupSessions() as $groupSession) {;
                    $groupSession->setStartDate($groupSession->getOriginalStartDate());
                    $groupSession->setEndDate($groupSession->getOriginalEndDate());
                }

                $this->entityManager->persist($groupSession);
            }
        }

        $activities = $course->getActivities();
        /** @var Activity $activity */
        foreach ($activities as $activity) {
            // Check if this Group has Published Scheduled Sessions
            if (count($activity->getGroupActivities()) > 0) {
                /** @var GroupActivity $groupActivity */
                foreach ($activity->getGroupActivities() as $groupActivity) {
                    $groupActivity->setStartDate($groupActivity->getOriginalStartDate());
                    $groupActivity->setEndDate($groupActivity->getOriginalEndDate());
                }

                $this->entityManager->persist($groupActivity);
            }
        }

        $course->setCountry($course->getOriginalCountry());
        $course->setTimezone($course->getOriginalTimezone());

        if ($this->updateRecord(self::$ENTITY_NAME, $course)) {
            $this->updateCourseMaterials( $request, $course );
            return true;
        } else {
            return false;
        }

    }

     /**
     * Function to get Course details from AIP
     *
     * @param Request $request Request Object
     * @throws ResourceNotFoundException
     * 
     */
    public function fetchDetailFromAIP(Request $request){
        try { 
            $aipResult = $this->aipService->getCourseDetail($request->get('class_number'), $request->get('term'));
        } catch (\Exception){
            throw new ResourceNotFoundException('Course not found');
        }

        return $aipResult;
    }

    /**
     * Function to get enrollments by Class Number & Term
     *
     * @param  Request $request Request Object
     * @throws ResourceNotFoundException
     *
     */
    public function fetchEnrollmentsFromAIP(Request $request) {
        try {
            $aipResult = $this->aipService->getEnrollment($request->get('class_number'), $request->get('term'));

            if ($request->get('raw')) {
                return $aipResult;
            } else {
                if (array_key_exists( 'enrollments', $aipResult)) {
                    $cleanedPersonArray = [];
                    foreach ($aipResult['enrollments'] as $personObj) {
                        $peopleSoftId = trim((string) $personObj['person']['person_id']);

                        /** @var User $user */
                        $user = $this->entityManager->getRepository(User::class)
                            ->findOneBy(['peoplesoft_id' => $peopleSoftId]);

                        /** @var UserProfileCache $cacheProfile */
                        $cacheProfile = null;
                        if ($user) {
                            if ($user->getCacheProfile()) {
                                $cacheProfile = $user->getCacheProfile();
                            }
                        }

                        if (strtolower(trim((string) $personObj['status'])) == 'active') {
                            $cleanedPersonArray[] = [
                                "peopleSoftId" => $peopleSoftId,
                                "firstname" => $cacheProfile ? $cacheProfile->getFirstname() : '-',
                                "lastname" => $cacheProfile ? $cacheProfile->getLastname() : '-',
                                "email" => $cacheProfile ? $cacheProfile->getUpnEmail() : '-'
                            ];
                        }
                    }

                    return $cleanedPersonArray;
                } else {
                    throw new ResourceNotFoundException('Enrollments not found');
                }
            }
        } catch (\Exception){
            throw new ResourceNotFoundException('Enrollments not found');
        }
    }

    private function updateCourseMaterials(Request $request, $course){
        try {
            $this->profileBookManager->generateProfileBook($request, $course->getProgrammeId());
        } catch (\Exception){
            $this->log("Unable to generate Profile Book (Update/Revert TZ)");
        }

        try {
            $this->sessionSheetManager->generateSessionSheetPDF($request, $course->getProgrammeId());
        } catch (\Exception){
            $this->log("Unable to generate Session Sheet (Update/Revert TZ)");
        }

        try {
            $this->calendarManager->generateProgrammeCalendar($request, $course->getProgrammeId());
        } catch (\Exception) {
            $this->log("Unable to generate Programme Calendar (Update/Revert TZ)");
        }

        $this->notify->setLogUuid($request);
        $this->notify->message($course, self::$ENTITY_NAME);
    }

    /**
     * Function to generate a unique UID for a course
     * Unique identifier for the course in the format C-XXXX-YYYYMM where XXXX is a unique course abbreviation
     * and YYYYMM is the date when this Course is created
     *
     * @param   String  $abbreviation   Course Code
     *
     * @return String
     */
    private function createUid($abbreviation)
    {

        $date = new \DateTime();

        return self::$UID_PREFIX
            . self::$UID_DELIMITER
            . strtoupper($abbreviation)
            . self::$UID_DELIMITER
            . strtoupper(date_format($date, "Y"))
            . strtoupper(date_format($date, "m"));
    }

}
