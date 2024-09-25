<?php

namespace esuite\MIMBundle\Service\Manager;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\User;
use Psr\Log\LoggerInterface;
use esuite\MIMBundle\Service\edotNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use esuite\MIMBundle\Service\BoxManager\BoxManager;
use esuite\MIMBundle\Service\edotCourseBackup as Backup;

use Exception;

use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Session;

use esuite\MIMBundle\Exception\BoxGenericException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class SessionManager extends Base
{

    /**
     * @var string
     *
     */
    public static $UID_PREFIX = "S";

    /**
     * @var string
     *
     */
    public static $UID_DELIMITER = "-";

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Session";

    protected $calendar;

    public function loadServiceManager(CalendarManager $calendar )
    {
        $this->calendar = $calendar;
    }


    /**
     * Function to create a Session
     *
     * @param Request       $request            Request Object
     * @param array         @data               an array of data to be passed to the function
     *
     * @throws BoxGenericException
     * @throws InvalidResourceException
     * @throws Exception
     * @throws ResourceNotFoundException
     *
     * @return Response
     */
    public function createSession(Request $request, $data)
    {
        $logUuid                = array_key_exists('logUuid', $data) ? $data['logUuid'] : null;
        $name                   = array_key_exists('name', $data) ? $data['name'] : null;
        $alternateSessionName   = array_key_exists('alternate_session_name', $data) ? $data['alternate_session_name'] : null;
        $description            = array_key_exists('description', $data) ? $data['description'] : null;
        $position               = array_key_exists('position', $data) ? $data['position'] : null;
        $course_id              = array_key_exists('course_id', $data) ? $data['course_id'] : null;
        $sessionCode            = array_key_exists('abbreviation', $data) ? $data['abbreviation'] : null;
        $sessionStart           = array_key_exists('slot_start', $data) ? $data['slot_start'] : null;
        $sessionEnd             = array_key_exists('slot_end', $data) ? $data['slot_end'] : null;
        $sessionColor           = array_key_exists('session_color', $data) ? $data['session_color'] : null;
        $isPublished            = array_key_exists('published', $data) ? $data['published'] : false;
        $optional_text          = array_key_exists('optional_text', $data) ? $data['optional_text'] : null;
        $is_scheduled           = array_key_exists('is_scheduled', $data) ? $data['is_scheduled'] : false;
        $remarks                = array_key_exists('remarks', $data) ? $data['remarks'] : null;
        
        if (!$description){
            throw new InvalidResourceException(['Description' => ['Session description is missing']]);
        }

        $description = trim((string) $description);
        if (mb_strlen($description) < 1){
            throw new InvalidResourceException(['Description' => ['Session description is missing']]);
        }

        if ($name){
            $name = trim((string) $name);
            if (mb_strlen($name) > 150 || $name === ''){
                $this->log("Error: Session title is more than 150. Submitted length: ".mb_strlen($name));
                throw new InvalidResourceException(['Name' => ['Session title cannot be more than 150']]);
            }
        } else {
            throw new InvalidResourceException(['Name' => ['Session title is missing']]);
        }

        $this->setLogUuid($logUuid);
        $this->notify->setLogUuidWithString($logUuid);

        $this->log( 'CREATING SESSION: ' . $name );
        
        /* @var $course Course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $course_id]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $this->checkReadWriteAccess($request,$course->getId());

        $existingSession = $this->entityManager
            ->getRepository(Session::class)
            ->findOneBy(['abbreviation' => $sessionCode]);

        if($existingSession) {
            throw new InvalidResourceException(['abbreviation' => ['This code is already used for another Session. Please enter another unique Session Code.']]);
        }

        $uid = $this->createUid($sessionCode,
            $course->getCountry(),
            new \DateTime($sessionStart)
        );

        $session = new Session();
        $session->setCourse($course);
        $session->setAbbreviation($sessionCode);
        $session->setName($name);
        $session->setDescription($description);
        $session->setPosition($position);
        $session->setAlternateSessionName($alternateSessionName);
        $session->setRemarks($remarks);

        if (!$position){
            $programme = $course->getProgramme();
            if($programme->getViewType() == 3) { //Hybrid
                if($course->getCourseTypeView() == 1) { //Tile View
                $sessionCount  = count($course->getSessions());
                $activityCount = count($course->getActivities());
                $session->setPosition(($sessionCount + $activityCount));
                }
                
            }
        }

        if($sessionStart) {
            $session->setStartDate(new \DateTime($sessionStart));
        }

        if($sessionEnd) {
            $session->setEndDate(new \DateTime($sessionEnd));
        }

        if ($sessionColor){
            $session->setSessionColor($sessionColor);
        }

        if ($optional_text){
            $session->setOptionalText($optional_text);
        }

        $session->setUid($uid);
        if($isPublished === true) {
            $session->setPublished($isPublished);
        }

        if($is_scheduled === true) {
            $session->setSessionScheduled($is_scheduled);
        }

        $this->validateObject($session);
        $session->setBoxFolderId("S3-".mktime(date("H")));

        try {
            $keyName = strtolower(self::$ENTITY_NAME);

            $this->validateObject($session);

            $em = $this->entityManager;
            $em->persist($session);
            $em->flush();

        } catch (Exception $e) {
            $this->log("Error " . $e->getCode() . ": " . $e->getMessage());
            throw $e;
        }

        $sessionObj = [$keyName => $session];

        $serializedData = $this->serializer->serialize($sessionObj, 'json');

        $responseObj = new Response($serializedData);
        $responseObj->setStatusCode(201);
        $responseObj->headers->set('Content-Type', 'application/json');

        if ($isPublished) {
            $this->notify->message($course, self::$ENTITY_NAME);

            $programmeId = $session->getCourse()->getProgramme()->getId();
            $this->log( "Generating Programme Calendar for " . $programmeId );
            $this->calendar->generateProgrammeCalendar( $request, $programmeId );
            $this->log( "Triggered Programme Calendar " . $programmeId );
        }

        return $responseObj;
    }

    /**
     * Function to retrieve an existing Session
     *
     * @param Request       $request            Request Object
     * @param String        $sessionId          id of the Session
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getSession(Request $request, $sessionId)
    {

        $scope = $this->getCurrentUserScope($request);
        $user = $this->getCurrentUserObj($request);

        /** @var Session $session */
        $session = $this->entityManager
            ->getRepository(Session::class)
            ->findOneBy(['id' => $sessionId]);

        if(!$session) {
            $this->log('Session not found');
            throw new ResourceNotFoundException('Session not found');
        }

        //provide the full object of subentities to be able to refresh attachments
        if( $scope == "edotstudent" ) {
            $session->serializeFullObject(TRUE);
            $session->checkGroupSessionAttachmentsFor($user->getPeoplesoftId());
            $session->setSerializeOnlyPublishedAttachments();

            $programme = $session->getCourse()->getProgramme();
            $programme->setRequestorId($user->getId());
            $programme->setForParticipant(true);
            $programme->setIncludeHidden(true);
        }

        $session->serializeOnlyPublished(TRUE);

        return [strtolower(self::$ENTITY_NAME) => $session];
    }

    /**
     * Function to update an existing Session
     *
     * @param Request       $request            Request Object
     * @param String        $sessionId          id of the Session
     *
     * @throws InvalidResourceException
     * @throws Exception
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function updateSession(Request $request, $sessionId)
    {
        $resetBackup = FALSE;

        $this->validateRelationshipUpdate('course_id', $request);

        // Find the Session
        /** @var $session Session */
        $session = $this->entityManager
            ->getRepository(Session::class)
            ->findOneBy(['id' => $sessionId]);

        if(!$session) {
            $this->log('Session not found');
            throw new ResourceNotFoundException('Session not found');
        }

        $this->checkReadWriteAccess($request,$session->getCourseId());

        $wasPublished = $session->getPublished();

        if( ( ($wasPublished == FALSE) && ($request->get('published') == TRUE) ) || ( ($wasPublished == TRUE) && ($request->get('published') == FALSE) )) {
            $resetBackup = TRUE;
        }

        // Set new values for session
        if($request->get('name')) {
            $name = $request->get('name');

            if ($name){
                $name = trim((string) $name);
                if (mb_strlen($name) > 150 || $name === ''){
                    $this->log("Error: Session title is more than 150. Submitted length: ".mb_strlen($name));
                    throw new InvalidResourceException(['Name' => ['Session title cannot be more than 150']]);
                } else {
                    $session->setName($request->get('name'));
                }
            } else {
                throw new InvalidResourceException(['Name' => ['Session title is missing']]);
            }
        }

        if($request->request->has('description')) {
            if ($request->get('description')) {
                $cleanedDesc = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $request->get('description'));
                $cleanedDesc = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $cleanedDesc);
                $cleanedDesc = preg_replace('#<link(.*?)>(.*?)</link>#is', '', $cleanedDesc);

                //removing inline js events
                $cleanedDesc = preg_replace("/([ ]on[a-zA-Z0-9_-]{1,}=\".*\")|([ ]on[a-zA-Z0-9_-]{1,}='.*')|([ ]on[a-zA-Z0-9_-]{1,}=.*[.].*)/","", $cleanedDesc);

                //removing inline js
                $cleanedDesc = preg_replace("/([ ]href.*=\".*javascript:.*\")|([ ]href.*='.*javascript:.*')|([ ]href.*=.*javascript:.*)/i","", $cleanedDesc);
                $session->setDescription($cleanedDesc);
            } else {
                throw new InvalidResourceException(['Description' => ['Session description is missing']]);
            }
        }

        if($request->request->has('position')) {
            $session->setPosition( (int)$request->get('position'));
        }
        if($request->get('slot_start')) {
            $session->setStartDate(new \DateTime($request->get('slot_start')));
        }
        if($request->get('slot_end')) {
            $session->setEndDate(new \DateTime($request->get('slot_end')));
        }

        if($request->get('slot_start') || $request->get('slot_end')) {
            $this->validateSessionDates($session);
        }

        if($request->get('published')) {
            $session->setPublished($request->get('published'));
        } else {
            if( $request->get('published') === false ) {
                $session->setPublished(false);
            }
        }
        if($request->get('alternate_session_name')) {
            $session->setAlternateSessionName($request->get('alternate_session_name'));
        }

        if($request->request->has('session_color')) {
            $session->setSessionColor($request->get('session_color'));
        }

        if($request->request->has('remarks')) {
            $session->setRemarks($request->get('remarks'));
        }

        if($request->request->has('optional_text')) {
            $session->setOptionalText($request->get('optional_text'));
        }

        if($request->request->has('is_scheduled')) {
            $session->setSessionScheduled($request->get('is_scheduled'));
        }

        $this->updateRecord(self::$ENTITY_NAME, $session);

        if($wasPublished || $request->get('published') == TRUE) {

            // push notifications if published
            $this->notify->setLogUuid($request);
            $this->notify->message($session->getCourse(), self::$ENTITY_NAME);
        }

        if ($resetBackup && ($session->getCourse()->getPublished() == TRUE) && ($session->getCourse()->getProgramme()->getPublished() == TRUE)) {
            $this->log("Needs a Backup reset.");
            // Update Course Backup
            $this->backup->updateCoursebackup($session->getCourse());

            $programmeId = $session->getCourse()->getProgramme()->getId();
            $this->log( "Generating Programme Calendar for " . $programmeId );
            $this->calendar->generateProgrammeCalendar( $request, $programmeId );
            $this->log( "Triggered Programme Calendar " . $programmeId );
        }

        /** @var $sessionUpdated Session */
        $sessionUpdated = $this->entityManager
            ->getRepository(Session::class)
            ->findOneBy(['id' => $sessionId]);

        return [strtolower(self::$ENTITY_NAME) => $sessionUpdated->serializeOnlyPublished(TRUE)];
    }

    /**
     * Function to delete an existing Session
     *
     * @param Request       $request            Request Object
     * @param String        $sessionId          id of the Session
     *
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return Response
     */
    public function deleteSession(Request $request, $sessionId)
    {

        $forceDelete = $request->query->has("force");

        // get Session
        /** @var $session Session */
        $session = $this->entityManager
            ->getRepository(Session::class)
            ->findOneBy(['id' => $sessionId]);

        if(!$session) {
            $this->log('Session not found');
            throw new ResourceNotFoundException('Session not found');
        }

        $this->checkReadWriteAccess($request,$session->getCourseId());

        $isPublished = $session->getPublished();
        $fileDocs = $session->getFileDocuments();
        $resetBackup = FALSE;
        $course = $session->getCourse();

        // send response
        if( !$session ) {
            $this->log("Session with id:" . $sessionId . " not found");
            throw new ResourceNotFoundException("Session with id:" . $sessionId . " not found");
        }

        //Delete item
        $em = $this->entityManager;
        $em->remove($session);
        $em->flush();

        // push notifications if published
        if ($isPublished) {
            $this->notify->setLogUuid($request);
            $this->notify->message($course, self::$ENTITY_NAME);
        }

        foreach($fileDocs as $fileDoc) {
            if($fileDoc->getPublishAt() < (new \DateTime())) {
                $resetBackup = TRUE;
                break;
            }
        }

        if($isPublished && $resetBackup) {
            $this->log("Needs a Backup reset.");
            // Update Course Backup
            $this->backup->updateCoursebackup($course);

            $programmeId = $session->getCourse()->getProgramme()->getId();
            $this->log( "Generating Programme Calendar for " . $programmeId );
            $this->calendar->generateProgrammeCalendar( $request, $programmeId );
            $this->log( "Triggered Programme Calendar " . $programmeId );
        }

        $responseObj = new Response();
        $responseObj->setStatusCode(204);

        return $responseObj;
    }

    /**
     * Function to assign a person(professor) to a Session
     *
     * @param Request $request Request Object
     * @param String $sessionId id of the Session
     *
     * @return array
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function assignProfessorToSession(Request $request, $sessionId)
    {

        $this->log("Assigning Professors to Session:".$sessionId);

        /** @var $session Session */
        $session = $this->entityManager
            ->getRepository(Session::class)
            ->findOneBy(['id' => $sessionId]);

        // send response
        if( !$session ) {
            $this->log("Session with id:" . $sessionId . " not found");
            throw new ResourceNotFoundException("Session with id:" . $sessionId . " not found");
        }

        $this->checkReadWriteAccess($request,$session->getCourseId());

        $professors = $request->get('professors');

        if(!$professors) {
            throw new InvalidResourceException(['professors' => ['At least one professor should be entered.']]);
        }

        $em = $this->entityManager;
        foreach ($professors as $psoftid) {
            //Find user by Peoplesoft ID
            $professor = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['peoplesoft_id' => $psoftid]);

            if ($professor) {
                $this->log("PROFESSOR FOUND: " . json_encode($professor));

                //Assign professor to session
                $session = $session->addProfessor($professor);
                $em->persist($session);

                $this->log("PROFESSOR ASSIGNED TO SESSION: " . $psoftid);
            } else {
                $this->log('PROFESSOR NOT FOUND');

                throw new InvalidResourceException(['professors' => ['Professor not found.']]);
            }

        }

        $em->flush();

        // push notifications if published
        if ($session->getPublished()) {
            $this->notify->setLogUuid($request);
            $this->notify->message($session->getCourse(), self::$ENTITY_NAME);
        }

        return ['professors' => $session->getProfessorList()];
    }

    /**
     * Function to unassign a person(professor) to a Session
     *
     * @param Request $request Request Object
     * @param String $sessionId id of the Session
     * @param String $peoplesoftId PeopleSoftId of the person to be removed as professor
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function unAssignProfessorToSession(Request $request, $sessionId, $peoplesoftId)
    {

        $this->log("Un-Assigning Professors to Session:".$sessionId);

        //Find Session
        /** @var $session Session */
        $session = $this->entityManager
            ->getRepository(Session::class)
            ->findOneBy(['id' => $sessionId]);

        // send response
        if( !$session ) {
            $this->log("Session with id:" . $sessionId . " not found");
            throw new ResourceNotFoundException("Session with id:" . $sessionId . " not found");
        }

        $this->checkReadWriteAccess($request,$session->getCourseId());

        $this->log("Looking for user with Peoplesoft id: ".$peoplesoftId);
        //Find user by Peoplesoft ID
        $professor = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peoplesoftId]);

        $this->log("PROFESSOR FOUND::".json_encode($professor));

        if($professor) {
            //Un-Assign professor from session
            $session = $session->removeProfessor($professor);
            $em = $this->entityManager;
            $em->persist($session);
            $em->flush();
        } else {
            throw new InvalidResourceException(['professors' => ['Professor not found.']]);
        }

        $this->log("Professor (Peoplesoft ID): ".$peoplesoftId." un-assigned from Session ".$sessionId);

        // push notifications if published
        if ($session->getPublished()) {
            $notify = $this->notify;
            $notify->setLogUuid($request);
            $notify->message($session->getCourse(), self::$ENTITY_NAME);
        }

        $this->log("Successfully unassigned " . $peoplesoftId . " from Session " . $sessionId);
    }


    /**
     * Function to generate a unique UID for a Session
     * Unique identifier for the session in the format S-XXXX-YYYYMM where XXXX is a unique Session abbreviation
     * and YYYYMM is the start date of the Session
     *
     * @param String        $abbreviation       Session code
     * @param String        $location           Location of where the session would be held
     * @param \DateTime     $startDate          Date when the session would start
     *
     * @return String
     */
    private function createUid($abbreviation, $location, $startDate) {

        return self::$UID_PREFIX
        .self::$UID_DELIMITER
        .strtoupper($abbreviation)
        .self::$UID_DELIMITER
        .strtoupper($location)
        .self::$UID_DELIMITER
        .strtoupper(date_format($startDate, "Y"))
        .strtoupper(date_format($startDate, "m"));
    }

    private function validateSessionDates(Session $session)
    {
        //Check if start_date of Session/Activity is after of just at start_date of Course
        if( $session->getStartDate() < $session->getCourse()->getStartDate() ||
            $session->getStartDate() > $session->getCourse()->getEndDate() ||
            $session->getEndDate() > $session->getCourse()->getEndDate() ) {

            throw new InvalidResourceException(['dates' => ['Sessions must be scheduled within the date range of a Course.']]);
        }
        return TRUE;
    }

}
