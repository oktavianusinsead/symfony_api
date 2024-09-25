<?php

namespace esuite\MIMBundle\Service\Manager;

use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use esuite\MIMBundle\Service\edotNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use esuite\MIMBundle\Service\BoxManager\BoxManager;
use esuite\MIMBundle\Service\edotCourseBackup as Backup;
use esuite\MIMBundle\Entity\Base as BaseEntity;

use esuite\MIMBundle\Entity\Activity;
use esuite\MIMBundle\Entity\GroupActivity;
use esuite\MIMBundle\Entity\Course;

use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ActivityManager extends Base
{
    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Activity";

    protected $calendar;

    public function loadServiceManager(CalendarManager $calendar)
    {
        $this->calendar = $calendar;
    }

    /**
     * Function to create an Activity
     *
     * @param Request $request Request Object
     *
     * @return Response
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     * @throws Exception
     */
    public function createActivity(Request $request) {
        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $request->get('course_id')]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $title = trim((string) $request->get('title'));
        if (strlen($title) > 150){
            throw new InvalidResourceException(['Title' => ['Activity title should not be more than 150 characters']]);
        }

        $this->checkReadWriteAccess($request,$course->getId());
        $requestQueryString = $request->request->all();

        if ($requestQueryString['multiple_schedules']) {
            $activityList = '';
            $datesOutOfRange = [];
            $datesUnableToCreate = [];
            if (count($requestQueryString['multiple_schedules']) > 0) {
                $listOfActivity = [];
                foreach ($requestQueryString['multiple_schedules'] as $schedule) {
                    $position = 0;
                    foreach($schedule['selectedDates'] as $selectedDate) {
                        try {
                            $activityString = $this->createActivityWithSchedule($requestQueryString, $schedule, $course, $position);
                            if ($activityString) {
                                $listOfActivity[] = $activityString;
                            }
                            $position =  $position + 1;
                            $this->log("Posisi ke: " . $position);
                        } catch ( InvalidResourceException $e) { // dates that are out of range
                            $datesOutOfRange[] = $selectedDate;
                            $this->log("Out of Course date range: " . $e->getMessage());
                        } catch (Exception $e) {
                            $this->log("Error creating activity: " . $e->getMessage());
                            $datesUnableToCreate[] = $selectedDate;
                        }
                    }
                    
                }

                if (count($listOfActivity) > 0) {
                    $activityList = $listOfActivity[0];
                    $this->notify->setLogUuid($request);
                    $this->notify->message($course, self::$ENTITY_NAME);

                    $programmeId = $course->getProgramme()->getId();
                    $this->log("Generating Programme Calendar for " . $programmeId);
                    $this->calendar->generateProgrammeCalendar($request, $programmeId);
                    $this->log("Triggered Programme Calendar " . $programmeId);
                }
            }

            if (count($datesOutOfRange) > 0 || count($datesUnableToCreate) > 0) {
                $errorReply = [];
                if (count($datesOutOfRange) > 0) {
                    $errorReply['outOfRange'] = ['Activities must be scheduled within the date range of a Course. Below dates are not created'];
                    $dateRangeStr = "";
                    foreach ($datesOutOfRange as $dateRange) {
                        $dateRangeStr.= $dateRange['slotStart']." - ".$dateRange['slotEnd'].", ";
                    }
                    $errorReply['outOfRangeList'] = $dateRangeStr;
                }

                if (count($datesUnableToCreate) > 0) {
                    $errorReply['unableToCreateActivity'] = ['Unable to create Activity for date below'];
                    $errorReply['unableToCreateActivityList'] = [implode(', ', $datesUnableToCreate)];
                }

                 throw new InvalidResourceException($errorReply);
            }

            $response = new Response($activityList);
            $response->setStatusCode(201);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else { // old implementation
            $activityList = $this->createActivityModel($requestQueryString, $course);
            $response = new Response($activityList);
            $response->setStatusCode(201);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
    }

    /**
     * @throws Exception
     */
    private function formatSlotDates(&$requestQueryString, $date) {
        $slot_start = new DateTime($date . "T23:00:00+00:00");
        $slot_start->modify("-1 day");

        /** @var DateTime $slot_start */
        $slot_end = new DateTime($date . "T23:59:59+00:00");

        $requestQueryString['slot_start'] = $slot_start->format('c');
        $requestQueryString['slot_end'] = $slot_end->format('c');
    }

     /**
     * @throws Exception
     */
    private function formatScheduledDates(&$requestQueryString, $date) {
        $slot_start = new DateTime(explode("T",(string) $date['slotStart'])[0] . "T23:00:00+00:00");
        $slot_start->modify("-1 day");

        /** @var DateTime $slot_start */
        $slot_end = new DateTime(explode("T",(string) $date['slotEnd'])[0]. "T23:59:59+00:00");

        $requestQueryString['slot_start'] = $slot_start->format('c');
        $requestQueryString['slot_end'] = $slot_end->format('c');
    }

    /**
     * Creates Activity model
     *
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    private function createActivityModel($data, $course): string
    {
        $this->log("ACTIVITY: ".$data['title']);
        $this->log("Creating Activity for course_id: ".$data['course_id']);
       
        $description = '';
        if ($data['description']) {
            $description = trim((string) $data['description']);
        }

        $activity = new Activity();
        $activity->setCourse($course);
        $activity->setTitle($data['title']);
        $activity->setType($data['type']);
        $activity->setDescription($description);
        $activity->setPosition(array_key_exists('position', $data) ? $data['position'] : null);
        $activity->setPublished($data['published']);

        if (!array_key_exists('position', $data)) {
            $programme = $course->getProgramme();
            if($programme->getViewType() == 3) { //Hybrid
                if($course->getCourseTypeView() == 1) { //Tile View
                $sessionCount  = count($course->getSessions());
                $activityCount = count($course->getActivities());
                $activity->setPosition(($sessionCount + $activityCount));
                }
            }
        }

        if ($data['slot_start']) {
            $activity->setStartDate(new DateTime($data['slot_start']));
        }

        if ($data['slot_end']) {
            $activity->setEndDate(new DateTime($data['slot_end']));
        }

        if ($data['slot_start'] || $data['slot_end']) {
            $this->validateActivityDates($activity);
        }

        if ($data['is_scheduled']) {
            $is_scheduled = $data['is_scheduled'];
            if ($is_scheduled === true)
                $activity->setActivityScheduled($is_scheduled);
        }

        return $this->persistActivity($activity);
    }

    /**
     * @throws OptimisticLockException
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function createActivityWithSchedule($requestData, $schedule, $course, $position): string
    {
        $this->log("ACTIVITY: ".$requestData['title']);
        $this->log("Creating Activity for course_id: ".$requestData['course_id']);

        $description = '';
        if (array_key_exists('description', $requestData)) {
            $description = trim((string) $requestData['description']);
        }

        $activity = new Activity();
        $activity->setCourse($course);
        $activity->setTitle($requestData['title']);
        $activity->setType($requestData['type']);
        $activity->setDescription($description);
        $activity->setPosition(array_key_exists('position', $requestData) ? $requestData['position'] : null);
        $activity->setPublished($requestData['published']);
        $getlastActivity = count($course->getActivities());
        
        if(!array_key_exists('position', $requestData)){
            $programme = $course->getProgramme();
            if($programme->getViewType() == 3) { //Hybrid
                if($course->getCourseTypeView() == 1) { //Tile View
                $sessionCount  = count($course->getSessions());
                $activityCount = count($course->getActivities());
                $activity->setPosition(($getlastActivity + $position));
                
                }
            }
            else{
                $sessionCount  = count($course->getSessions());
                $activityCount = count($course->getActivities());
                $activity->setPosition(($sessionCount + $activityCount));
               
            }
        }

        if($requestData['slot_start']) {
            $activity->setStartDate(new DateTime($requestData['slot_start']));
        }

        if($requestData['slot_end']) {
            $activity->setEndDate(new DateTime($requestData['slot_end']));
        }

        if($requestData['slot_start'] || $requestData['slot_end']) {
            $this->validateActivityDates($activity);
        }

       
        $activity->setActivityScheduled(true);
        $group = $this->findById('Group', $schedule['attendeeId']);

        $groupActivity = new GroupActivity();
        $groupActivity->setGroup($group);
        $groupActivity->setActivity($activity);
        $groupActivity->setStartDate(new DateTime($schedule['selectedDates'][$position]['slotStart']));
        $this->log('set start date' . json_encode(new DateTime($schedule['selectedDates'][$position]['slotStart'])));
        $groupActivity->setOriginalStartDate($groupActivity->getStartDate());
        $this->log('originalStartDate' . json_encode($groupActivity->getStartDate()));
        $groupActivity->setEndDate(new DateTime($schedule['selectedDates'][$position]['slotEnd']));
        $groupActivity->setOriginalEndDate($groupActivity->getEndDate());
        $groupActivity->setLocation($schedule['location']);
        $groupActivity->setPublished(true);

        $persistingActivity = $this->persistActivity($activity);
        $this->entityManager->persist($groupActivity);
        $this->entityManager->flush();


        return $persistingActivity;
    }

    /**
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function persistActivity(BaseEntity $data): string
    {
        $em = $this->entityManager;
        $em->persist($data);
        $em->flush();

        return $this->serializer->serialize([strtolower(self::$ENTITY_NAME) => $data], 'json');;
    }

    /**
     * Function to retrieve an existing Activity
     *
     * @param Request       $request            Request Object
     * @param String        $activityId         id of the Activity
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getActivity(Request $request, $activityId)
    {

        /** @var Activity $activity */
        $activity = $this->entityManager
            ->getRepository(Activity::class)
            ->findOneBy(['id' => $activityId]);

        if(!$activity) {
            $this->log('Activity not found');
            throw new ResourceNotFoundException('Activity not found');
        }
        
        if ($activity->getCourse()->getCourseTypeView() === 1) { // Tile View
            $activity->serializeOnlyPublished(TRUE);
        }

        return [strtolower(self::$ENTITY_NAME) => $activity->serializeOnlyPublished(TRUE)];
    }

    /**
     * Function to retrieve existing Activities
     *
     * @param Request       $request            Request Object
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getActivities(Request $request)
    {

        $activities = [];
        $ids = $request->get('ids');

        foreach($ids as $id)
        {
            $this->log("ACTIVITY: ".$id);

            /** @var Activity $activity */
            $activity = $this->entityManager
                ->getRepository(Activity::class)
                ->findOneBy(['id' => $id]);

            if(!$activity) {
                $this->log('Activity not found');
                throw new ResourceNotFoundException('Activity not found');
            }

            array_push($activities, $activity->serializeOnlyPublished(TRUE));
        }

        return ['activities' => $activities];
    }

    /**
     * Function to update an existing Activity
     *
     * @param Request $request Request Object
     * @param String $activityId id of the Activity
     *
     * @return Response
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function updateActivity(Request $request, $activityId)
    {

        $this->log("ACTIVITY:".$request->get('name'));

        $this->validateRelationshipUpdate('course_id', $request);

        // Find the Activity
        /** @var Activity $activity */
        $activity = $this->entityManager
            ->getRepository(Activity::class)
            ->findOneBy(['id' => $activityId]);

        if(!$activity) {
            $this->log('Activity not found');
            throw new ResourceNotFoundException('Activity not found');
        }

        $this->checkReadWriteAccess($request,$activity->getCourseId());

        $resetActivity = false;
        if( ($activity->getPublished() === false && $request->get('published') ) || ($activity->getPublished() && $request->get('published') === false) ) {
            $resetActivity = true;
        }

        // Set new values for Activity
        if($request->get('title')) {
            $title = trim((string) $request->get('title'));
            if (strlen($title) > 150){
                throw new InvalidResourceException(['Title' => ['Activity title should not be more than 150 characters']]);
            } else {
                $activity->setTitle($title);
            }
        }

        $type = $request->get('type');
        
        if(isset($type)) {
            $activity->setType($request->get('type'));
        }

        if($request->request->has('description')) {
            
            $activity->setDescription($request->get('description'));
        }
        if($request->get('slot_start')) {
            $activity->setStartDate(new DateTime($request->get('slot_start')));
        }

        if($request->get('slot_end')) {
            $activity->setEndDate(new DateTime($request->get('slot_end')));
        }

        if($request->get('slot_start') || $request->get('slot_end')) {
            $this->validateActivityDates($activity);
        }

        if($request->request->has('position')) {
            $activity->setPosition( (int)$request->get('position'));
        }
        if($request->get('published')) {
            $activity->setPublished($request->get('published'));
        } else {
            if( $request->get('published') === false) {
                $activity->setPublished(false);
            }
        }

        if($request->request->has('is_scheduled')) {
            $activity->setActivityScheduled($request->get('is_scheduled'));
        }

        if ($activity->getCourse()->getCourseTypeView() === 1) { // Tile View
            $activity->serializeOnlyPublished(TRUE);
        }

        $responseObj = $this->updateRecord(self::$ENTITY_NAME, $activity);

        // push notifications if published
        $this->notify->setLogUuid($request);
        $this->notify->message($activity->getCourse(), self::$ENTITY_NAME);

        if ($resetActivity) {
            $programmeId = $activity->getCourse()->getProgramme()->getId();
            $this->log( "Generating Programme Calendar for " . $programmeId );
            $this->calendar->generateProgrammeCalendar( $request, $programmeId );
            $this->log( "Triggered Programme Calendar " . $programmeId );
        }

        return $responseObj;
    }

    /**
     * Function to delete an existing Activity
     *
     * @param Request       $request            Request Object
     * @param String        $activityId         id of the Activity
     *
     * @throws ResourceNotFoundException
     *
     * @return Response
     */
    public function deleteActivity(Request $request, $activityId)
    {

        /** @var Activity $activity */
        $activity = $this->entityManager
            ->getRepository(Activity::class)
            ->findOneBy(['id' => $activityId]);

        if(!$activity) {
            $this->log('Activity not found');
            throw new ResourceNotFoundException('Activity not found');
        }

        $this->checkReadWriteAccess($request,$activity->getCourseId());

        $course = $activity->getCourse();

        $em = $this->entityManager;
        $em->remove($activity);
        $em->flush();

        $responseObj = new Response();
        $responseObj->setStatusCode(204);

        // push notifications if published
        $this->notify->setLogUuid($request);
        $this->notify->message($course, self::$ENTITY_NAME);

        $programmeId = $activity->getCourse()->getProgramme()->getId();
        $this->log( "Generating Programme Calendar for " . $programmeId );
        $this->calendar->generateProgrammeCalendar( $request, $programmeId );
        $this->log( "Triggered Programme Calendar " . $programmeId );

        return $responseObj;
    }

    /**
     * Function to validate the date given for an activity
     *
     * @param Activity        $activity       Activity Object
     *
     * @throws InvalidResourceException
     *
     * @return boolean
     */
    private function validateActivityDates(Activity $activity)
    {
        //Check if start_date of Session/Activity is after of just at start_date of Course
        if( $activity->getStartDate() < $activity->getCourse()->getStartDate() ||
            $activity->getStartDate() > $activity->getCourse()->getEndDate() ||
            $activity->getEndDate() > $activity->getCourse()->getEndDate() ) {

            throw new InvalidResourceException(['dates' => ['Activities must be scheduled within the date range of a Course.']]);
        }
        return TRUE;
    }

}
