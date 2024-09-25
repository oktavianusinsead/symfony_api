<?php

namespace Insead\MIMBundle\Controller;

use Exception;

use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Group;
use Insead\MIMBundle\Entity\Session;
use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Entity\AdminSessionLocation;
use Insead\MIMBundle\Entity\GroupSession;
use Insead\MIMBundle\Entity\GroupSessionAttachment;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Service\Manager\CalendarManager;
use Insead\MIMBundle\Service\StudyCourseBackup;
use Insead\MIMBundle\Service\StudyNotify;
use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\S3ObjectManager;
use Doctrine\ORM\EntityManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Group")]
class GroupSessionController extends BaseController
{
    
    /**
     * @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "GroupSession";

    /**
     * @var string
     *  Name of the Attachment Entity
     */
    public static $ATTACHMENT_ENTITY_NAME = "GroupSessionAttachment";

    /**
     *  @var string
     *  Name of the key
     */
    public static $EMBER_NAME = "group_session";


    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public CalendarManager $calendar,
                                EntityManager $em,
                                AuthToken $authToken,
                                LoginManager $login,
                                public StudyNotify $notify,
                                public StudyCourseBackup $studyCourseBackup
                                )
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $calendar->loadServiceManager($s3, $login);
        $notify = new StudyNotify($baseParameterBag, $logger);
        $studyCourseBackup = new StudyCourseBackup($em, $baseParameterBag, $logger);
    }

    #[Put("/group-sessions")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Schedule a new Group Session. This API endpoint is restricted to coordinators only.")]
    public function createGroupSessionAction(Request $request)
    {
        $this->setLogUuid($request);

        //Check if Group exists
        /** @var Group $group */
        $group = $this->findById('Group', $request->get('group_id'));

        $this->checkReadWriteAccess($request,$group->getCourseId());

        //Check if Session exists
        /** @var Session $session */
        $session = $this->findById('Session', $request->get('session_id'));

        /** @var User $user */
        $user = $this->getCurrentUserObj( $request );

        $groupSession = new GroupSession();
        $groupSession->setSession($session);
        $groupSession->setGroup($group);
        $this->processDateSession($request, $groupSession);
        $groupSession->setLocation($request->get('location'));
        $groupSession->setPublished($request->get('published'));

        //setting old flag to be always true as this would have no bearing on the new implementation
        //necessary to avoid need to update the web and ios app
        $groupSession->setHandoutsPublished(true);

        try {
            $responseObj = $this->create(self::$EMBER_NAME, $groupSession);
        } catch(Exception $e) {
            if( $e::class == 'Doctrine\\DBAL\\DBALException'
                || $e::class == \Doctrine\DBAL\Exception\UniqueConstraintViolationException::class
            ) {
                throw new InvalidResourceException(['session' => ['This Section is already associated with this Session.']]);
            }
            throw $e;
        }

        if(($request->get('published') == TRUE) && ($session->getPublished() == TRUE)) {
            // push notifications if published
            
            $this->notify->setLogUuid($request);
            $this->notify->message($session->getCourse(), self::$ENTITY_NAME);
        }

        if( $request->get('location') ) {
            $em = $this->doctrine->getManager();

            $location = $em
                ->getRepository(AdminSessionLocation::class)
                ->findOneBy( ['user' => $user, 'course' => $session->getCourse()] );

            if( !$location ) {
                $location = new AdminSessionLocation();
                $location->setCourse($session->getCourse());
                $location->setUser($user);
            }

            $location->setLocation($request->get('location'));

            $em->persist($location);
            $em->flush();
        }

        $programmeId = $session->getCourse()->getProgrammeId();
        $this->log( "Generating Programme Calendar for " . $programmeId );
        $this->calendar->generateProgrammeCalendar( $request, $programmeId );
        $this->log( "Triggered Programme Calendar " . $programmeId );

        return $responseObj;
    }

    #[Post("/group-sessions/{groupSessionId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupSessionId ", description: "id of the group session to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update a Group Session. This API endpoint is restricted to coordinators only.")]
    public function updateGroupSessionAction(Request $request, $groupSessionId)
    {
        $this->setLogUuid($request);

        // Find the GroupSession
        /** @var GroupSession $groupSession */
        $groupSession = $this->findById(self::$ENTITY_NAME, $groupSessionId);

        $course = $groupSession->getSession()->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());
        $this->processDateSession($request, $groupSession);

        if($request->get('location')) {
            $groupSession->setLocation($request->get('location'));
        }
        if($request->get('published')) {
            $groupSession->setPublished($request->get('published'));
        }

        //setting old flag to be always true as this would have no bearing on the new implementation
        //necessary to avoid need to update the web and ios app
        $groupSession->setHandoutsPublished(true);

        $responseObj = $this->update(self::$EMBER_NAME, $groupSession);

        if($groupSession->getSession()->getPublished() == TRUE) {
            // push notifications if published
           
            $this->notify->setLogUuid($request);
            $this->notify->message($course, self::$ENTITY_NAME);

            // Update Course Backup
           
            $this->studyCourseBackup->updateCoursebackup($course);
        }

        $programmeId = $groupSession->getSession()->getCourse()->getProgrammeId();
    
        $this->log( "Generating Programme Calendar for " . $programmeId );
        $this->calendar->generateProgrammeCalendar( $request, $programmeId );

        $this->log( "Triggered Programme Calendar " . $programmeId );

        return $responseObj;
    }

    private function processDateSession(Request $request, &$groupSession){
        if($request->get('start_date')) {
            $groupSession->setStartDate(new \DateTime($request->get('start_date')));
            $groupSession->setOriginalStartDate($groupSession->getStartDate());
        }
        if($request->get('end_date')) {
            $groupSession->setEndDate(new \DateTime($request->get('end_date')));
            $groupSession->setOriginalEndDate($groupSession->getEndDate());
        }
    }

    #[Post("/group-sessions/{groupSessionId}/publish-handouts")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupSessionId ", description: "id of the group session to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to publish handouts under the Group Session. This API endpoint is restricted to coordinators only.")]
    public function groupSessionPublishHandoutAction(Request $request, $groupSessionId)
    {
        $this->setLogUuid($request);

        $selectedItems = explode( ',', (string) $request->get('selected_items') );
        $toBePublished = $request->get('to_publish');

        $em = $this->doctrine->getManager();

        // Find the GroupSession
        /** @var GroupSession $groupSession */
        $groupSession = $this->findById(self::$ENTITY_NAME, $groupSessionId);

        $course = $groupSession->getSession()->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        $now = new \DateTime();
        $future_date = new \DateTime();
        $future_date->add(new \DateInterval('P50Y'));

        $handouts = $groupSession->getSession()->getAllHandouts();

        foreach($handouts as $handout) {
            $params = ['groupSession' => $groupSession, 'handout' => $handout, 'now' => $now, 'future_date' => $future_date];

            $attachment_record = $this->checkGroupSessionAttachment($params);

            if( in_array( $attachment_record->getId(), $selectedItems ) ) {
                //PUBLISH
                if( $toBePublished ) {
                    //only publish handouts that have publish_at >= to now()
                    if( $attachment_record->getPublishAt() >= $now ) {
                        $attachment_record->setPublishAt( $now );

                        $em = $this->doctrine->getManager();
                        $em->persist($attachment_record);

                        $this->log("Publishing Group Attachment: " . $attachment_record->getId());
                    }

                //UNPUBLISH
                } else {
                    //only unpublish handouts that have be published
                    if( $attachment_record->getPublishAt() < $future_date ) {
                        $attachment_record->setPublishAt( $future_date );

                        $em = $this->doctrine->getManager();
                        $em->persist($attachment_record);

                        $this->log("Unpublishing Group Attachment: " . $attachment_record->getId());
                    }
                }
            }
        }

        //setting old flag to be always true as this would have no bearing on the new implementation
        //necessary to avoid need to update the web and ios app
        $groupSession->setHandoutsPublished(true);

        $responseObj = $this->update(self::$EMBER_NAME, $groupSession);

        if($groupSession->getSession()->getPublished() == TRUE) {
            // push notifications if published
            
            $this->notify->setLogUuid($request);
            $this->notify->message($course, self::$ENTITY_NAME);

            // Update Course Backup
            
            $this->studyCourseBackup->updateCoursebackup($course);
        }

        $em->flush();

        return $responseObj;
    }

    #[Get("/group-sessions/{groupSessionId}")]
    #[Allow(["scope" => "studyadmin,studysuper,studyssvc,studysvc"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupSessionId ", description: "id of the group session to be retrieved", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a GroupSession.")]
    public function getGroupSessionAction(Request $request, $groupSessionId)
    {
        $this->setLogUuid($request);

        $now = new \DateTime();
        $future_date = new \DateTime();
        $future_date->add(new \DateInterval('P50Y'));

        // Find the GroupSession
        $groupSession = $this->findById(self::$ENTITY_NAME, $groupSessionId);

        //ensure group session attachment records are existing
        $handouts = $groupSession->getSession()->getAllHandouts();
        foreach($handouts as $handout) {
            $params = ['groupSession' => $groupSession, 'handout' => $handout, 'now' => $now, 'future_date' => $future_date];

            $this->checkGroupSessionAttachment($params);
        }
        $responseObj = $groupSession;

        return ['group_session' => $responseObj];
    }

    #[Get("/group-sessions")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple GroupSessions.")]
    public function getGroupSessionsAction(Request $request)
    {
        $this->setLogUuid($request);

        $now = new \DateTime();
        $future_date = new \DateTime();
        $future_date->add(new \DateInterval('P50Y'));

        $groupSessions = [];
        $ids = $request->get('ids');

        foreach($ids as $id)
        {
            $this->log("GROUP SESSION: ".$id);

            // Find the GroupSession
            $groupSession = $this->findById(self::$ENTITY_NAME, $id);

            //ensure group session attachment records are existing
            $handouts = $groupSession->getSession()->getAllHandouts();
            foreach($handouts as $handout) {
                $params = ['groupSession' => $groupSession, 'handout' => $handout, 'now' => $now, 'future_date' => $future_date];
                $this->checkGroupSessionAttachment($params);
            }

            array_push($groupSessions, $groupSession);
        }
        return ['group_session' => $groupSessions];
    }

    #[Delete("/group-sessions/{groupSessionId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupSessionId ", description: "id of the group session to be deleted", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Group Session. This API endpoint is restricted to coordinators only.")]
    public function deleteGroupSessionAction(Request $request, $groupSessionId)
    {
        $this->setLogUuid($request);

        $this->log("DELETING GROUP SESSION: " . $groupSessionId);

        /** @var GroupSession $groupSession */
        $groupSession = $this->findById(self::$ENTITY_NAME, $groupSessionId);
        $wasPublished = $groupSession->getPublished();

        $course = $groupSession->getSession()->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        /** @var Session $session */
        $session = $groupSession->getSession();

        // Delete Group Session from database
        $responseObj = $this->deleteById(self::$ENTITY_NAME, $groupSessionId);

        if($session->getPublished() && $wasPublished) {
            // push notifications if session and group session were published
            
            $this->notify->setLogUuid($request);
            $this->notify->message($course, self::$ENTITY_NAME);
        }

        $programmeId = $groupSession->getSession()->getCourse()->getProgrammeId();
        
        $this->log( "Generating Programme Calendar for " . $programmeId );
        $this->calendar->generateProgrammeCalendar( $request, $programmeId );
        $this->log( "Triggered Programme Calendar " . $programmeId );

        return $responseObj;
    }

    private function checkGroupSessionAttachment(array $params) {
        $groupSession = $params['groupSession'];
        $handout = $params['handout'];
        $now = $params['now'];
        $future_date = $params['future_date'];

        $criterion = [
            'session' => $groupSession->getSession()->getId(),
            'group_session' => $groupSession->getId(),
            'attachment_type' => $handout->getAttachmentType(),
            'attachment_id' => $handout->getId(),
        ];

        $attachment_record = $this->doctrine
            ->getRepository('Insead\MIMBundle\Entity\\' . self::$ATTACHMENT_ENTITY_NAME)
            ->findOneBy($criterion);

        //no existing group session attachment record
        if( !$attachment_record ) {
            $this->log("Manually creating group-session-attachment record for " . $groupSession->getSession()->getId() . "-" . $groupSession->getId() );

            $attachment_record = new GroupSessionAttachment();
            $attachment_record->setCreated($now);
            $attachment_record->setUpdated($now);
            $attachment_record->setSession($groupSession->getSession());
            $attachment_record->setGroupSession($groupSession);
            $attachment_record->setAttachmentType($handout->getAttachmentType());
            $attachment_record->setAttachmentId($handout->getId());

            //placeholder now()+50yrs
            $attachment_record->setPublishAt( $future_date );

            $em = $this->doctrine->getManager();
            $em->persist($attachment_record);
            $em->flush();
        }

        return $attachment_record;
    }
}
