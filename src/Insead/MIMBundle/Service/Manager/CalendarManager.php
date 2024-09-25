<?php

namespace Insead\MIMBundle\Service\Manager;

use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\StudyNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use Insead\MIMBundle\Service\BoxManager\BoxManager;
use Insead\MIMBundle\Service\StudyCourseBackup as Backup;
use Insead\MIMBundle\Service\S3ObjectManager;


use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Activity;
use Insead\MIMBundle\Entity\GroupActivity;
use Insead\MIMBundle\Entity\GroupSession;
use Insead\MIMBundle\Entity\Session;

use Doctrine\Common\Collections\Criteria;

use Symfony\Component\HttpFoundation\Request;

use Insead\MIMBundle\Exception\ResourceNotFoundException;


class CalendarManager extends Base
{
    private array $colors = [[ 'name' => 'Red',       'id' => 0, 'hex' => '#ED5A78' ], [ 'name' => 'Purple',    'id' => 1, 'hex' => '#B38DD9' ], [ 'name' => 'Blue',      'id' => 2, 'hex' => '#479AED' ], [ 'name' => 'Yellow',    'id' => 3, 'hex' => '#E8C251' ], [ 'name' => 'Green',     'id' => 4, 'hex' => '#90C561' ], [ 'name' => 'Chocolate', 'id' => 5, 'hex' => '#BA5E0B' ]];

    protected S3ObjectManager $s3;
    protected LoginManager $login;

    private static string $BEARER_HEADER = 'Bearer';

    public function loadServiceManager(S3ObjectManager $s3, LoginManager $login )
    {
        $this->s3                   = $s3;
        $this->login                = $login;
    }


    /**
     * Function to extractCalendar
     *
     * @param Request $request Request Object
     *
     * @return array
     * @throws ResourceNotFoundException
     */
    public function extractCalendar(Request $request)
    {

        $this->log("Get Calendar");

        $scope = $this->getCurrentUserScope($request);
        $user = $this->getCurrentUserObj($request);

        $calendar = ["courses" => [], "dates" => [], "items" => []];

        //use current week if no date range is given
        $day = date('w');
        $startDateString = date('F d Y', strtotime('-'.$day.' days')) . " 00:00:00 UTC";
        $endDateString = date('F d Y', strtotime('+'.(6-$day).' days')) . " 23:59:59 UTC";

        $startDate = new \DateTime;
        $startDate->setTimestamp( strtotime($startDateString) );

        $endDate = new \DateTime;
        $endDate->setTimestamp( strtotime($endDateString) );

        if( $request->get('filter_start') && $request->get('filter_end') ) {
            if(
                strtotime((string) $request->get('filter_start'))
                && strtotime((string) $request->get('filter_end'))
            ) {
                $startDate = new \DateTime;
                $startDate->setTimestamp( strtotime((string) $request->get('filter_start')) );

                $endDate = new \DateTime;
                $endDate->setTimestamp( strtotime((string) $request->get('filter_end')) );
            } else {
                $this->log("Invalid Date Range given for Admin Calendar");
            }
        }

        $dateDiff = date_diff($endDate,$startDate)->days + 1 ;

        $this->log("Generating Calendar between " . $startDate->format('m/d/y') . " and " . $endDate->format('m/d/y') . " - (" . $dateDiff . " days)" );

        $criteria = new Criteria();
        $expr = $criteria->expr();
        $criteria->where(
            $expr->andX(
                $expr->lte('start_date',$startDate),
                $expr->gte('end_date',$startDate),
                $expr->lte('end_date',$endDate)
            )
        );
        $criteria->orWhere(
            $expr->andX(
                $expr->gte('start_date',$startDate),
                $expr->lte('start_date',$endDate),
                $expr->gte('end_date',$endDate)
            )
        );
        $criteria->orWhere(
            $expr->andX(
                $expr->lte('start_date',$startDate),
                $expr->gte('end_date',$endDate)
            )
        );
        $criteria->orWhere(
            $expr->andX(
                $expr->gte('start_date',$startDate),
                $expr->lte('end_date',$endDate)
            )
        );

        $result = $this->entityManager
            ->getRepository(Course::class)
            ->matching($criteria);

        $this->log( "Courses found: " . count($result) );
        if( $result ) {
            /* @var $itemObj Course */
            foreach ($result as $itemObj) {

                $itemObj->getProgramme()->setRequestorId($user->getId());
                $itemObj->getProgramme()->setForParticipant(true);
                $itemObj->getProgramme()->setIncludeHidden(true);
                if( $scope == "studysuper" ) {
                    $itemObj->getProgramme()->setRequestorScope($scope);
                }

                $courseInfo = ["id" => $itemObj->getId(), "start_date" => $itemObj->getStartDate(), "end_date" => $itemObj->getEndDate(), "abbreviation" => $itemObj->getAbbreviation(), "name" => $itemObj->getName(), "programme" => $itemObj->getProgramme()->getName(), "programme_code" => $itemObj->getProgramme()->getCode(), "programme_id" => $itemObj->getProgrammeId(), "participant_count" => count( $itemObj->getStudents() ), "location" => $itemObj->getCountry(), "subject" => $itemObj->getPsSessionCode()];

                $calendar["courses"][ $itemObj->getId() ] = $courseInfo;
            }

            $calendar["dates"]["start"] = $startDate->format('Y-m-d');
            $calendar["dates"]["end"] = $endDate->format('Y-m-d');

            for($i=0;$i<$dateDiff;$i++) {
                $theDate = clone $startDate;
                $theDate = $theDate->modify("+".$i." day");

                $calendar["items"][$i] = [];

                /* @var $itemObj Course */
                foreach ($result as $itemObj) {
                    if( $theDate >= $itemObj->getStartDate() && $theDate <= $itemObj->getEndDate() ) {
                        $calendar["items"][$i][] = $itemObj->getId();
                    }
                }
            }
        }

        return ['calendar' => $calendar];
    }

    /**
     * Function to generate calendar for a programme
     *
     * @param Request       $request            Request Object
     * @param   int         $programmeId    id of the programme to be generated
     *
     * @return array
     * @throws ResourceNotFoundException|InvalidResourceException
     *
     */
    public function generateProgrammeCalendar(Request $request, $programmeId)
    {
        $this->log("Initiate generateProgrammeCalendar for " . $programmeId);

        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(["id"=>$programmeId]);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme not found');
        }

        $this->checkReadWriteAccessToProgramme($request,$programme);

        $serviceToken = "";

        //Get Authorization Header
        $headers = $request->headers;
        $authHeader = $headers->get('Authorization');

        //Check if Header value starts with 'Bearer'
        if($authHeader) {
            // API request. Check access_token in 'users' table
            $oauthAccessToken = trim(substr($authHeader, strlen(self::$BEARER_HEADER), strlen($authHeader)));
            $serviceToken = $this->login->generateServiceToken( $oauthAccessToken );

            $this->log("Service Token is generated for Programme Calendar");
        }

        $calendarInfo = ["id" => $programmeId, "service_token" => $serviceToken->getOauthAccessToken()];
        
        $result = $this->s3->uploadToS3(
            "preprocessed-resources/raw-calendar/" . $programme->getId() . ".json",
            json_encode($calendarInfo),
            true
        );

        $this->log( "Requested programme calendar generation" . json_encode($result) );

        $courses = $programme->getPublishedCourses();
        /** @var Course $course */
        foreach( $courses as $course ) {
            $this->log("Need to reset backup for course " . $course->getId());

            $backupService = $this->backup;
            $backupService->updateCoursebackup($course);
        }

        return $result;
    }

    /**
     * Function to get calendar info for a programme
     *
     * @param Request $request Request Object
     * @param int $programmeId id of the programme to be generated
     *
     * @return array
     * @throws \Exception
     * @throws ResourceNotFoundException
     */
    public function getProgrammeCalendarInfo(Request $request, $programmeId)
    {
        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(["id"=>$programmeId]);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme not found');
        }

        $prefix = substr( $this->cleanName($programme->getName()), 0, 150);
        if( $programme->getStartDate() ) {
            $prefix = $prefix . "-" . $programme->getStartDate()->format("MY");
        }

        $startDate = $programme->getStartDate();
        $endDate = $programme->getEndDate();

        if( !is_null($programme->getStartDate()) ) {
            $startDate = $programme->getStartDate()->format("Y-m-d");
        }
        if( !is_null($programme->getEndDate()) ) {
            $endDate = $programme->getEndDate()->format("Y-m-d");
        }

        $viewType = ($programme->getViewType() ?: 0);
        if ($viewType === 1) {
            $data = $this->prepareTileViewCalendar($programme);
        } elseif ($viewType === 999){
            $data = $this->prepareScheduledCalendar($programme);
        } elseif ($viewType === 3){
            $data = $this->prepareHybridProgramme($programme);
        } else {
            $data = $this->prepareScheduledProgramme($programme);
        }

        return ["name" => $programme->getName(), "code"=> $programme->getCode(), "companyLogo"=>$programme->getCompanyLogo(), "startDate" => $startDate, "endDate" => $endDate, "prefix" => $prefix, "weeks" => $data, "viewType" => $viewType];
    }

     /**
     * Function to prepare the data for hybrid programme
     *
     * @param Programme $programme
     * @return array
     * @throws \Exception
     */

    private function prepareHybridProgramme($programme) {
        $scheduledArrayEvents = $this -> prepareScheduledProgramme($programme);
        $tileArrayEvents = $this ->  prepareTileViewCalendar($programme);
        $final = array_merge($scheduledArrayEvents, $tileArrayEvents);
        ksort($final);

        return $final;
    }
    /**
     * Function to prepare the data for scheduled programme
     *
     * @param Programme $programme
     * @return array
     * @throws \Exception
     */
    private function prepareScheduledProgramme($programme){

        $eventCourse = [];
        $this->log( "Schedule Programme---> ");
        //get all events
        /** @var Course $course */
        foreach( $programme->getPublishedCourses() as $course ) {
            if($programme->getViewType() == 3) {
                if($course->getCourseTypeView() == '0') {
                    $this->sortEventCourse($course,$eventCourse);
                }
            } else {
                $this->sortEventCourse($course,$eventCourse);
            }
        }

        $data = [];
        foreach ($eventCourse as $eventDates) {
            $days = [];

            //plot the events
            foreach ($eventDates["eventDates"] as $key => $items) {
                $eventDate = new \DateTime($key);
                $events = [];

                foreach ($items as $event) {
                    $event["eventStime"] = $this->getEventTime($event["start"]);
                    $event["eventEtime"] = $this->getEventTime($event["end"]);
                    $event["duration"] = $this->calculateEventDuration($event["start"], $event["end"]);

                    unset($event["start"]);
                    unset($event["end"]);

                    $events[] = $event;
                }

                usort($events, fn($a, $b) => $a['timeTag'] <=> $b['timeTag']);

                $days[] = ["daytitle" => $eventDate->format("jS F Y"), "events" => $events];
            }

            if (count($days) > 0) {
                $data[] = ["coursetitle" => $eventDates["title"], "coursedate" => $eventDates["date"], "timezone" => $eventDates["timezone"], "days" => $days, "viewType" => $eventDates["viewType"]];
            }
        }

        return $data;
    }

    private function sortEventCourse($course, &$eventCourse){
        $timezone = $course->getTimezone();
        $timezone = str_replace(":","",$timezone);

        $sessions = $course->getPublishedSessions();
        $activities = $course->getPublishedActivities();

        $eventDates = [];
        $this->populateEventsWithSession( $eventDates, $sessions, $timezone );
        $this->populateEventsWithActivity( $eventDates, $activities, $timezone );

        $sDate = $course->getStartDate()->setTimezone(new \DateTimeZone($course->getTimezone()));
        $eDate = $course->getEndDate()->setTimezone(new \DateTimeZone($course->getTimezone()));

        ksort($eventDates);
        $eventCourse[] = ["title" => $course->getName(), "date" => $sDate->format("d-M-Y") . " to " . $eDate->format("d-M-Y"), "eventDates" => $eventDates, "timezone" => $timezone, "viewType" => $course->getCourseTypeView()];
    }

    /**
     * Function to prepare the data for scheduled calendar
     *
     * @param Programme $programme
     * @return array
     * @throws \Exception
     */
    private function prepareTileViewCalendar($programme){
        $courses = [];

        /** @var Course $course */
        foreach ($programme->getCourses() as $course){
            if ($course->getPublished()) {
                $proceed = false;
                $tileView = false;
                if($programme->getViewType() == 3) {
                    if($course->getCourseTypeView() == 1) {
                        $tileView = $course->getCourseTypeView() === 1;
                        $proceed = true;
                    }
                } else {
                    $proceed = true;
                    $tileView = $programme->getViewType() === 1;
                }

                if ($proceed) {
                    $timezone = $course->getTimezone();
                    $timezone = str_replace(":", "", $timezone);
                    $sessions = $course->getPublishedSessions();
                    $activities = $course->getPublishedActivities();

                    $events = [];
                    $this->populateEventsWithSession($events, $sessions, $timezone, false, $tileView);
                    $this->populateEventsWithActivity($events, $activities, $timezone, $tileView);
                    if (count($events) > 0) {
                        $this->setDateAndSort($course, $timezone, $events, $keyID, $courseStartDate, $courseEndDate, $allEventsInCourseNewKeys);
                        $courses[$keyID . $course->getId()] = [
                            "courseiID" => $course->getId(),
                            "courseName" => $course->getName(),
                            "couseStartDate" => $courseStartDate->format("d M y"),
                            "couseEndDate" => $courseEndDate->format("d M y"),
                            "viewType" => $course->getCourseTypeView(),
                            "events" => $allEventsInCourseNewKeys
                        ];
                    }
                }
            }
        }

        ksort($courses);

        return $courses;
    }

    /**
     * @throws \Exception
     */
    private function setDateAndSort($course, $timezone, $events, &$keyID, &$courseStartDate, &$courseEndDate, &$allEventsInCourseNewKeys) {
        $courseStartDate = $this->offsetTime($course->getStartDate(), $timezone);
        $courseEndDate   = $this->offsetTime($course->getEndDate(), $timezone);
        $keyID = $courseStartDate->format("Ymd");
        $allEventsInCourse = [];
        foreach ($events as $courseEvents) {
            foreach ($courseEvents as $event) {

                if (array_key_exists('start', $event)){
                    if (strlen($event['start'] > 0)){
                        $eventStartDate = $event['start'] ;
                        $newDate = $eventStartDate->format("jS M Y g:iA");
                        $event['start'] = $newDate;
                    }
                }

                if (array_key_exists('end', $event)){
                    if (strlen($event['end'] > 0)){
                        $eventEndDate = $event['end'] ;
                        $newDate = $eventEndDate->format("jS M Y g:iA");
                        $event['end'] = $newDate;
                    }
                }

                $allEventsInCourse[$event['position']] = $event;
            }
        }
        ksort($allEventsInCourse);
        $allEventsInCourseNewKeys = array_values($allEventsInCourse);
    }

    /**
     * Function to prepare the data for scheduled calendar
     *
     * @param Programme $programme
     * @return array
     * @throws \Exception
     */
    private function prepareScheduledCalendar($programme){
        $eventDates = [];
        $weeks = [];
        $rawWeeks = [];

        //get all events
        /** @var Course $course */
        foreach( $programme->getPublishedCourses() as $course ) {

            $timezone = $course->getTimezone();
            $timezone = str_replace(":","",$timezone);

            $sessions = $course->getPublishedSessions();
            $activities = $course->getPublishedActivities();

            $this->populateEventsWithSession( $eventDates, $sessions, $timezone );
            $this->populateEventsWithActivity( $eventDates, $activities, $timezone );
        }

        //determine the weeks involved
        foreach( $eventDates as $key => $items ) {
            $eventDate = new \DateTime( $key );

            $day = $eventDate->format("w");
            $year = $eventDate->format("Y");
            $week = $eventDate->format("W");

            //if the programme starts on a sunday and the item is a sunday, it should display for the next week
            if( $programme->getStartsOnSunday() && $day == 0 ) {
                $week = $week + 1;
            }

            //week starts on a monday
            if( !$programme->getStartsOnSunday() ) {
                $day = $day - 1;

                if( $day < 0 ) {
                    $day = 6;
                }

            }

            $interval = new \DateInterval('P' . ($day) . 'D');
            $weekStart = new \DateTime( $key );
            $weekStart->sub( $interval );

            $interval = new \DateInterval('P6D');
            $weekEnd = new \DateTime( $weekStart->format('Ymd') );
            $weekEnd->add( $interval );

            $weekLabel = $year . "_" . $week;

            if( !isset($rawWeeks[ $weekLabel ]) ) {
                $rawWeeks[ $weekLabel ] = ["weekStartDate" => $weekStart->format('M-d'), "weekEndDate"   => $weekEnd->format('M-d'), "timezone" => "", "min" => 24, "max" => 0, "days" => [], "groups" => [], "group_name" => "", "viewType" => $course->getCourseTypeView()];

                for($i = 0; $i < 7; $i++) {
                    $interval = new \DateInterval('P' . $i . 'D');
                    $date = new \DateTime( $weekStart->format('Ymd') );
                    $date->add( $interval );

                    $rawWeeks[$weekLabel]["days"][] = ["daytitle" => $date->format("d D"), "events" => []];
                }
            }

            $min = $rawWeeks[ $weekLabel ]["min"];
            $max = $rawWeeks[ $weekLabel ]["max"];

            foreach( $items as $event ) {
                /** @var \DateTime $start */
                $start = $event["start"];

                /** @var \DateTime $end */
                $end = $event["end"];

                if( $start && $start->format('H') < $min ) {
                    $min = $start->format('H');
                }

                if( $end ) {
                    $endHour = $end->format('H');
                    if( $end->format("i") > 0 ) {
                        $endHour = intval($endHour) + 1;
                    }


                    if( $endHour > $max ) {
                        $max = $endHour;
                    }

                    if( $max > 24 ) {
                        $max = 24;
                    }
                }

                //get the first timezone data
                if( $rawWeeks[ $weekLabel ]["timezone"] == "" ) {
                    $rawWeeks[ $weekLabel ]["timezone"] = $event["timezone"];
                }

                //plot groups
                if( $event["group"] != "" ) {
                    if( array_search( $event["group"], $rawWeeks[ $weekLabel ]["groups"] ) === false ) {
                        $rawWeeks[$weekLabel]["groups"][] = $event["group"];
                    }
                }
            }

            $rawWeeks[ $weekLabel ]["min"] = intval($min);
            $rawWeeks[ $weekLabel ]["max"] = intval($max);

        }

        //plot the events
        foreach( $eventDates as $key => $items ) {
            $eventDate = new \DateTime( $key );

            $day = $eventDate->format("w");
            $year = $eventDate->format("Y");
            $week = $eventDate->format("W");

            //if the programme starts on a sunday and the item is a sunday, it should display for the next week
            if( $programme->getStartsOnSunday() && $day == 0 ) {
                $week = $week + 1;
            }

            //week starts on a monday
            if( !$programme->getStartsOnSunday() ) {
                $day = $day - 1;

                if( $day < 0 ) {
                    $day = 6;
                }

            }

            $weekLabel = $year . "_" . $week;

            $duration = $rawWeeks[ $weekLabel ]["max"] - $rawWeeks[ $weekLabel ]["min"];

            foreach( $items as $event ) {
                $event["starttime"] = $this->datetimeToPercent( $event["start"], $rawWeeks[ $weekLabel ]["min"], $duration );
                $event["endtime"] = $this->datetimeToPercent( $event["end"], $rawWeeks[ $weekLabel ]["min"], $duration );

                $event["duration"] = $this->calculateEventDuration( $event["start"], $event["end"] );

                unset($event["start"]);
                unset($event["end"]);

                $rawWeeks[$weekLabel]["days"][$day]["events"][] = $event;

            }
        }

        //duplicate details for weeks that have groups
        foreach( $rawWeeks as $key => $weekItem ) {
            $groups = $weekItem["groups"];
            $data = $weekItem;

            if( count($groups) > 0 ) {
                //remove old data
                unset($rawWeeks[$key]);

                foreach ($groups as $group) {
                    $newKey = $key . "_" . $group["id"];

                    $rawWeeks[$newKey] = $data;
                    $rawWeeks[$newKey]["group_name"] = $group["name"];
                    $rawWeeks[$newKey]["group_id"] = $group["id"];
                }
            }
        }

        //ensure each week only has that belong to the group + everyone only
        foreach( $rawWeeks as $key => $weekItem ) {
            $groups = $weekItem["groups"];
            $days = $weekItem["days"];

            $groupId = $weekItem["group_id"];

            if( count($groups) > 0 ) {
                unset($weekItem["groups"]);

                foreach( $days as $dayIndx => $day ) {
                    $dayEvents = $day["events"];

                    $cleanedEvents = [];

                    foreach( $dayEvents as $eventIndx => $event ) {
                        if( isset($event["group"]["id"]) ) {
                            if( $event["group"]["id"] == "" || $event["group"]["id"] == $groupId ) {
                                //only consider items for the group and items for everyone
                                array_push( $cleanedEvents, $event );
                            }

                            //everyone
                        } else {
                            //only consider items for the group and items for everyone
                            array_push( $cleanedEvents, $event );
                        }
                    }

                    $rawWeeks[$key]["days"][$dayIndx]["events"] = $cleanedEvents;

                }
            }
        }

        //sort by key
        ksort($rawWeeks);

        //translate raw weeks into a series
        foreach( $rawWeeks as $weekItem ) {
            $weeks[] = $weekItem;
        }

        return $weeks;
    }

    /**
     * check if a calendar is available for the programme
     * checks if user's Token is valid
     * @param Request       $request            Request Object
     * @param integer       $programmeId        id of the programme
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getCalendar(Request $request, $programmeId)
    {
        $user = $this->getCurrentUserObj($request);

        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if( $programme ) {

            $prefix = substr( $this->cleanName($programme->getName()), 0, 150);
            if( $programme->getStartDate() ) {
                $prefix = $prefix . "-" . $programme->getStartDate()->format("MY");
            }

            $this->log("Generating temporary calendar URLs for " . $user->getPeoplesoftId());

            return ["calendars" => ["id"                        => $programme->getId(), "programme"                 => $programme->getId(), "url"                       => $this->s3->generateCalendarTempUrl( $programmeId, $user->getPeoplesoftId() ), "prefix"                    => $prefix]];

        } else {
            $this->logger->error('Invalid Programme');
            throw new ResourceNotFoundException('Programme not found');
        }
    }

    private function populateEventsWithSession(&$eventDates, $sessions, $timezone, $includeOnlyWithGroupSession = true, $tileView = false) {
        /** @var Session $session */
        foreach( $sessions as $session ) {

            if ($tileView) {
                $event = ["start" => "", "end" => "", "timeTag" => "TBC", "name" => $session->getName(), "location" => "", "host" => $session->getProfessorList(), "hostName" => [], "color" => "", "timezone" => $timezone, "group" => [], "position" => ($session->getPosition() ?: 0), "sessionColor" => $session->getSessionColor(), "remarks" => ($session->getRemarks() ?: "")];

            } else {
                $event = ["start"        => "", "end"          => "", "timeTag"      => "TBC", "name"         => $session->getName(), "location"     => "", "host"         => $session->getProfessorList(), "hostName"     => [], "color"        => "", "timezone"     => $timezone, "group"        => "", "position"     => ($session->getPosition() ?: 0), "sessionColor" => $session->getSessionColor(), "remarks"      => ($session->getRemarks() ?: "")];
            }

            $totalHosts = count($session->getProfessorList());

            if ($totalHosts > 0) {
                foreach ($session->getProfessorList() as $psoftID) {
                    $professor = $this->entityManager
                        ->getRepository(User::class)
                        ->findOneBy(["peoplesoft_id" => $psoftID]);

                    $event["hostName"][] = $professor->getFirstname() . " " . $professor->getLastname();
                }
            }

            if( count($session->getGroupSessions()) > 0) {
                /** @var GroupSession $groupSession */
                foreach($session->getGroupSessions() as $groupSession) {
                    $gsStartDate = $this->offsetTime( $groupSession->getStartDate(), $timezone );
                    $gsEndDate = $this->offsetTime( $groupSession->getEndDate(), $timezone );

                    $event["start"] = $gsStartDate;
                    $event["end"] = $gsEndDate;
                    $event["timeTag"] = $gsStartDate->format("H:i");
                    $event["location"] = $groupSession->getLocation();
                    $event["color"] = $this->findColor( $groupSession->getGroup()->getColour() );

                    //only consider groups that are not "everyone"
                    if( $groupSession->getGroup()->getColour() >= 0 ) {

                        if ($tileView) {
                            $event["group"][] = ["id" => $groupSession->getGroupId(), "name" => $groupSession->getGroup()->getName(), "sdate" => $gsStartDate->format("jS M Y : g:iA"), "edate" => $gsEndDate->format("jS M Y : g:iA"), "color" => $this->findColor($groupSession->getGroup()->getColour())];
                        } else {
                            $event["group"] = ["id" => $groupSession->getGroupId(), "name" => $groupSession->getGroup()->getName()];
                        }
                    }

                    if( !isset($eventDates[ $gsStartDate->format("Ymd") ]) ) {
                        $eventDates[ $gsStartDate->format("Ymd") ] = [];
                    }

                    $eventDates[$gsStartDate->format("Ymd")][] = $event;
                }
            } else {
                if (!$includeOnlyWithGroupSession) {
                    $gsStartDate = $this->offsetTime($session->getStartDate(), $timezone);
                    $gsEndDate = $this->offsetTime($session->getEndDate(), $timezone);

                    $event["start"] = $gsStartDate;
                    $event["end"] = $gsEndDate;
                    $event["timeTag"] = "TBC";
                    $event["location"] = "";
                    $event["color"] = "";
                    $event["group"] = [];

                    if (!isset($eventDates[$gsStartDate->format("Ymd")])) {
                        $eventDates[$gsStartDate->format("Ymd")] = [];
                    }
                    $eventDates[$gsStartDate->format("Ymd")][] = $event;
                }
            }
        }
    }

    private function populateEventsWithActivity(&$eventDates, $activities, $timezone, $tileView = false) {
        /** @var Activity $activity */
        foreach( $activities as $activity ) {

            if ($tileView) {
                $event = ["start" => "", "end" => "", "timeTag" => "TBC", "name" => $activity->getTitle(), "desc" => strip_tags($activity->getDescription()), "location" => "", "host" => "", "color" => "", "timezone" => $timezone, "group" => [], "position" => $activity->getPosition(), "activity" => $activity->getType()];
            } else {
                $event = ["start" => "", "end" => "", "timeTag" => "TBC", "name" => $activity->getTitle(), "location" => "", "host" => "", "color" => "", "timezone" => $timezone, "group" => "", "position" => $activity->getPosition(), "activity" => $activity->getType()];
            }

            if( count($activity->getGroupActivities()) > 0 ) {
                /** @var GroupActivity $groupActivity */
                foreach($activity->getGroupActivities() as $groupActivity) {
                    $gaStartDate = $this->offsetTime( $groupActivity->getStartDate(), $timezone );
                    $gaEndDate = $this->offsetTime( $groupActivity->getEndDate(), $timezone );

                    $event["start"] = $gaStartDate;
                    $event["end"] = $gaEndDate;
                    $event["timeTag"] = $gaStartDate->format("H:i");
                    $event["location"] = $groupActivity->getLocation();
                    $event["color"] = $this->findColor( $groupActivity->getGroup()->getColour() );

                    //only consider groups that are not "everyone"
                    if( $groupActivity->getGroup()->getColour() >= 0 ) {

                        if ($tileView) {
                            $event["group"][] = ["id" => $groupActivity->getGroupId(), "name" => $groupActivity->getGroup()->getName(), "sdate" => $gaStartDate->format("jS M Y : g:iA"), "edate" => $gaEndDate->format("jS M Y : g:iA"), "color" => $this->findColor($groupActivity->getGroup()->getColour())];
                        } else {
                            $event["group"] = ["id" => $groupActivity->getGroupId(), "name" => $groupActivity->getGroup()->getName()];
                        }
                    }

                    if( !isset($eventDates[ $gaStartDate->format("Ymd") ]) ) {
                        $eventDates[ $gaStartDate->format("Ymd") ] = [];
                    }

                    $eventDates[$gaStartDate->format("Ymd")][] = $event;
                }
            } else {
                $gaStartDate = $this->offsetTime($activity->getStartDate(), $timezone);
                $gaEndDate = $this->offsetTime($activity->getEndDate(), $timezone);

                $event["start"] = $gaStartDate;
                $event["end"] = $gaEndDate;

                $eventDates[$gaStartDate->format("Ymd")][] = $event;
            }

        }
    }

    /**
     * @param $timezone
     * @return \DateTime
     * @throws \Exception
     */
    private function offsetTime( \DateTime $date, $timezone ) {
        $utc = timezone_open("+0000");

        $newDate = new \DateTime( $date->format("m/d/Y H:i:s"), $utc );
        return $newDate->setTimezone(timezone_open($timezone));
    }

    private function findColor($index) {
        $color = "";

        if( isset($this->colors[$index]) ) {
            $color = $this->colors[$index]['hex'];
        }

        return $color;
    }

    private function datetimeToPercent( \DateTime $date, $min, $duration ) {

        $time = intval($date->format("H")) + ( intval($date->format("i")) / 60 );

        $plot = ((($time - $min) / $duration ) * 100);
        return number_format( $plot , 2, '.', '');
    }

    private function calculateEventDuration( \DateTime $start, \DateTime $end ) {
        $startTime = intval($start->format("H")) + ( intval($start->format("i")) / 60 );
        $endTime = intval($end->format("H")) + ( intval($end->format("i")) / 60 );

        return $endTime - $startTime;
    }

    private function getEventTime( \DateTime $eventTime) {
        return $eventTime->format("h:i A");
    }
}
