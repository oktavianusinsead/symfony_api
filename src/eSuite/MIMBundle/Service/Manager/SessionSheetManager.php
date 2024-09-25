<?php
/**
 * Created by PhpStorm.
 * User: jeffersonmartin
 * Date: 12/11/18
 * Time: 11:27 AM
 */

namespace esuite\MIMBundle\Service\Manager;

use DateTimeInterface;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\UserProfileCache;
use esuite\MIMBundle\Entity\UserToken;
use esuite\MIMBundle\Service\S3ObjectManager;


use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\GroupSession;
use esuite\MIMBundle\Entity\Session;

use Symfony\Component\HttpFoundation\Request;

use esuite\MIMBundle\Exception\ResourceNotFoundException;


class SessionSheetManager extends Base
{
    protected $s3;
    protected $login;
    protected $programmeManager;
    protected $programmeCompanyLogoManager;
    protected $courseManager;

    private static $BEARER_HEADER   = 'Bearer';

    private static $replyKey = 'session-sheets';

    public function loadServiceManager(S3ObjectManager $s3, LoginManager $login, ProgrammeManager $programmeManager, ProgrammeCompanyLogoManager $programmeCompanyLogoManager, CourseManager $courseManager )
    {
        $this->s3                          = $s3;
        $this->login                       = $login;
        $this->programmeManager            = $programmeManager;
        $this->programmeCompanyLogoManager = $programmeCompanyLogoManager;
        $this->courseManager               = $courseManager;
    }

    /**
     * Function to extract all published sessions in a programme
     *
     * @param Request $request Request Object
     * @param integer $programmeId Id of the Programme to pull the sessions
     *
     * @return array
     */
    
    public function getAllPublishedSessions(Request $request, $programmeId)
    {
        $userId =  $this->getCurrentUserId($request);
        $scope = $this->getCurrentUserScope($request);

        $programmeDetails = $this->programmeManager->getProgramme($request,$programmeId);
        $allSession = [];
        if ($programmeDetails){
            /** @var Programme $programmeDetails */
            $programmeDetails = $programmeDetails['programme'];


            if( $scope == 'edotssvc' || $scope=='edotsvc' ) {
                $programmeDetails->setOverriderReadonly(true);
            }
            $cspLogo          = $this->programmeCompanyLogoManager->getProgrammeCompanyLogo($request,$programmeId, $userId, $scope);

            $courses          = $this->programmeManager->getCoursesFromProgramme($request,$programmeId);
            $courses          = $courses['courses'];

            $allSession['programmedetails']['id']          = $programmeDetails->getId();
            $allSession['programmedetails']['name']        = $programmeDetails->getName();
            $allSession['programmedetails']['description'] = $programmeDetails->getWelcome();
            $allSession['programmedetails']['csplogo']     = $cspLogo['logo'];
            $startdate = $programmeDetails->getStartDate(true);
            $endDate = $programmeDetails->getEndDate(true);

            $allSession['programmedetails']['startdate']   = $startdate->format("Y-m-d H:i:s");
            $allSession['programmedetails']['startdateTimeZone']   = "GMT".$programmeDetails->getStartDateTimeZone();

            $allSession['programmedetails']['enddate']   =  $endDate->format("Y-m-d H:i:s");
            $allSession['programmedetails']['enddateTimeZone']   =  "GMT".$programmeDetails->getEndDateTimezone();
            $allSession['programmedetails']['location']    = "-";

            $allSession['programmedetails']['director']          = [];
            $allSession['programmedetails']['coordinator']       = [];
            $allSession['programmedetails']['advisor']           = [];
            $allSession['programmedetails']['totalParticipants'] = [];
            $sessions                                            = [];
            $allParticipants                                     = [];
            $sessionTileView                                     = [];

            /** @var Course $course */
            foreach ($courses as $course){

                if (!$course->getPublished()) continue;

                $allSession['programmedetails']['director']    = array_merge($allSession['programmedetails']['director'],$course->getDirectors());
                $allSession['programmedetails']['coordinator'] = array_merge($allSession['programmedetails']['coordinator'],$course->getCoordination());
                $allSession['programmedetails']['advisor']     = array_merge($allSession['programmedetails']['advisor'],$course->getesuiteTeam());
                $allParticipants           = array_merge($allParticipants,$course->getStudents());

                $cnt = 0;
                /** @var Session $tmpSession */
                foreach($course->getSessions() as $tmpSession){
                    if($programmeDetails->getViewType() == 3) {
                        if ($course->getCourseTypeView() === 1) {
                            $scheduleTileView = $this->getTileViewScheduleDate($tmpSession);
                            $remarks = $tmpSession->getRemarks();
                        } else {
                            $scheduleTileView = [];
                            $remarks = '';
                        }
                    }
                    else if($programmeDetails->getViewType() == 1) {
                        $scheduleTileView = $this->getTileViewScheduleDate($tmpSession);
                        $remarks = $tmpSession->getRemarks();
                    }
                    else{
                        $scheduleTileView = [];
                        $remarks = '';
                    }

                    $sortDate = $this->getSessionSortDate($tmpSession);

                    /** @var Session $tmpSession */
                    array_push($sessions, ["name"        => $tmpSession->getName(), "hosts"        => $tmpSession->getProfessorList(), "description" => $tmpSession->getDescription(), "location"    => $course->getCountry(), "sortDate" => $sortDate]);

                    $position = ((!empty($tmpSession->getPosition()) || $tmpSession->getPosition() === 0) ? $tmpSession->getPosition() : $cnt);
                    /** @var Session $tmpSession */
                    array_push($sessionTileView, ["name"        => $tmpSession->getName(), "hosts"        => $tmpSession->getProfessorList(), "description" => $tmpSession->getDescription(), "location"    => $course->getCountry(), "sortDate" => $course->getStartDate()->format("Ymd") . $position, "scheduleTileView" => $scheduleTileView, "remarks" => $remarks]);

                    $cnt++;
                }
            }

            if ($sessions && count($sessions) > 0) {
                usort($sessions, fn($a, $b) => $a['sortDate'] > $b['sortDate']);
            }

            if ($sessionTileView && count($sessionTileView) > 0) {
                usort($sessionTileView, fn($a, $b) => $a['sortDate'] > $b['sortDate']);
            }

            $allSession['programmedetails']['director']          = array_values(array_unique($allSession['programmedetails']['director']));
            $allSession['programmedetails']['coordinator']       = array_values(array_unique($allSession['programmedetails']['coordinator']));
            $allSession['programmedetails']['advisor']           = array_values(array_unique($allSession['programmedetails']['advisor']));
            $allParticipants                                     = array_values(array_unique($allParticipants));
            $allSession['programmedetails']['totalParticipants'] = count($allParticipants);
            $allSession['sessions'] = ($course->getCourseTypeView() === 1 ? $sessionTileView : $sessions);
        }

        return [self::$replyKey => $allSession];
    }


    /**
     * Function to get sorting date for sessions
     *
     * @param Session       $session
     *
     * @return \DateTime
     */
    public function getSessionSortDate($session){
        /** @var Session $session */
        $sortdate =null;
        if(count($session->getGroupSessions())>0) {

            $groupSessionLowestDate =null;
            foreach($session->getGroupSessions() as $groupSession){

                /* @var GroupSession $groupSession*/
                $groupSession->getStartDate();
                if($groupSessionLowestDate===null){
                    $groupSessionLowestDate =  $groupSession->getStartDate();
                }else if ($groupSessionLowestDate>$groupSession->getStartDate()){

                    $groupSessionLowestDate =  $groupSession->getStartDate();
                }
            }
            $sortdate =$groupSessionLowestDate;

        }else{
            $sortdate= $session->getStartDate();
        }

        return $sortdate;
    }

    /**
     * Function to get the schedule date for sessions
     *
     * @param Session       $session
     *
     * @return array
     */
    public function getTileViewScheduleDate($session){
        /** @var Session $session */
        $schedule = [];
        if(count($session->getGroupSessions())>0) {

            $groupSessionLowestDate =null;
            foreach($session->getGroupSessions() as $groupSession){

                /* @var GroupSession $groupSession*/
                $groupSession->getStartDate();
                if($groupSessionLowestDate===null){
                    $groupSessionLowestDate =  $groupSession->getStartDate();
                    $groupSessionLocation =  $groupSession->getLocation();
                }else if ($groupSessionLowestDate>$groupSession->getStartDate()){
                    $groupSessionLowestDate =  $groupSession->getStartDate();
                    $groupSessionLocation =  $groupSession->getLocation();
                }
            }

            $schedule['date'] = $groupSessionLowestDate;
            $schedule['location']=  $groupSessionLocation;

        }else{
            $schedule = "";
        }

        return $schedule;
    }

    /**
     * Function to fetch session sheet from S3
     *
     * @param Request       $request            Request Object
     * @param integer       $programmeId        Id of the Programme to pull the sessions
     *
     * @return array
     */
    public function getSessionSheet(Request $request, $programmeId)
    {
        $responseArray = [];
        $pdfDetails = $this->s3->getObjectDetailsFromedotBackUp('session-files/'.$programmeId.'.pdf');
        if ($pdfDetails){
            $programme = $this->programmeManager->getProgramme($request,$programmeId);
            $programme = $programme['programme'];

            $SessionPrefix = substr( $this->cleanName($programme->getName()), 0, 150);
            if( $programme->getStartDate() ) {
                $SessionPrefix = $SessionPrefix . "-" . $programme->getStartDate()->format("MY");
            }
            $responseArray['id'] =$programmeId;
            $responseArray['programme'] =$programmeId;
            $responseArray['session_prefix'] = $SessionPrefix;
            $responseArray['LastModified'] = $pdfDetails['LastModified']->format(DateTimeInterface::ATOM);
            $responseArray['signedURL'] = $this->s3->generateTempUrlItemFromedotBackUp('session-files/'.$programmeId.'.pdf');
        } else {
            $responseArray['id'] =$programmeId;
            $responseArray['programme'] =$programmeId;
            $responseArray['session_prefix'] = "";
            $responseArray['LastModified'] = "";
            $responseArray['signedURL'] = "";
        }

        return [self::$replyKey => $responseArray];
    }

    /**
     * Function to generate session sheet to S3
     *
     * @param Request $request Request Object
     * @param integer $programmeId Id of the Programme to pull the sessions
     *
     * @return array
     * @throws ResourceNotFoundException
     */
    public function generateSessionSheetPDF(Request $request, $programmeId)
    {
        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if($programme){

            $names = [];
            /** @var array $courseSubscription */
            $courseSubscriptions = $programme->getCourseSubscriptions();

            /** @var CourseSubscription $courseSubscription */
            foreach ($courseSubscriptions as $courseSubscription) {
                /** @var User $user */
                $user = $courseSubscription->getUser();

                /** @var UserProfileCache $cacheProfile */
                $cacheProfile = $user->getUserProfileCache();
                if (!$cacheProfile) continue;

                $arrayKeySearch = array_search($user->getPeoplesoftId(), array_column($names, 'peoplesoft_id'));
                if ($arrayKeySearch === false){
                    array_push($names, [
                        "peoplesoft_id" => $user->getPeoplesoftId(),
                        "firstname" => $cacheProfile->getFirstname(),
                        "lastname" => $cacheProfile->getLastname(),
                    ]);
                }
            }

            $allPublishedSessions = $this->getAllPublishedSessions($request, $programmeId);
            $allPublishedSessions["session-sheets"]["names"] = $names;

            $result = $this->s3->uploadToS3(
                "preprocessed-resources/data-session-sheet/" . $programmeId . ".json",
                json_encode($allPublishedSessions),
                true
            );

            $this->log("Request Session Sheet generation: " . json_encode($result));

            if ($result) {

                $courses = $programme->getPublishedCourses();

                /** @var Course $course */
                foreach( $courses as $course ) {
                    $this->log("Need to reset backup for course " . $course->getId());

                    $backupService = $this->backup;
                    $backupService->updateCoursebackup($course);
                }

                return [self::$replyKey => ['status' => 'success']];

            } else {
                return [self::$replyKey => ['status' => 'error']];
            }
        }else{
            throw new ResourceNotFoundException('Programme not found');

        }
    }
}
