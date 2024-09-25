<?php

namespace esuite\MIMBundle\Controller;

use Exception;

use esuite\MIMBundle\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use esuite\MIMBundle\Entity\Activity;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Group;
use esuite\MIMBundle\Entity\GroupActivity;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Service\Manager\CalendarManager;
use esuite\MIMBundle\Service\edotNotify;
use Psr\Log\LoggerInterface;
use esuite\MIMBundle\Service\S3ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\Manager\LoginManager;
use esuite\MIMBundle\Service\edotCourseBackup;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Activity")]
class GroupActivityController extends BaseController
{

    /**
     * @var string
     *  Name of the Entity
     */
    public static string $ENTITY_NAME = "GroupActivity";

    /**
     *  @var string
     *  Name of the key
     */
    public static string $EMBER_NAME = "group_activity";

    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                AuthToken $authToken,
                                LoginManager $login,
                                private readonly CalendarManager $calendar,
                                private readonly edotNotify $notify)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $this->calendar->loadServiceManager($s3, $login);
    }
    #[Put("/group-activities")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Schedule a new Group Activity. This API endpoint is restricted to coordinators only")]
    public function createGroupActivityAction(Request $request)
    {
        $this->setLogUuid($request);

        //Check if Group exists
        /** @var Group $group */
        $group = $this->findById('Group', $request->get('group_id'));

        $this->checkReadWriteAccess($request,$group->getCourseId());

        //Check if Activity exists
        /** @var Activity $activity */
        $activity = $this->findById('Activity', $request->get('activity_id'));

        $groupActivity = new GroupActivity();
        $groupActivity->setActivity($activity);
        $groupActivity->setGroup($group);
        $this->processDateActivity($request, $groupActivity);

        $groupActivity->setLocation($request->get('location'));
        $groupActivity->setPublished($request->get('published'));

        try {
            $responseObj = $this->create(self::$EMBER_NAME, $groupActivity);
        } catch(Exception $e) {
            if( $e::class == 'Doctrine\\DBAL\\DBALException'
                || $e::class == \Doctrine\DBAL\Exception\UniqueConstraintViolationException::class
            ) {
                throw new InvalidResourceException(['session' => ['This Section is already associated with this Activity.']]);
            }
            throw $e;
        }

        if(($request->get('published') == TRUE) && ($activity->getPublished() == TRUE)) {
            // push notifications if published
            
            $this->notify->setLogUuid($request);
            $this->notify->message($activity->getCourse(), self::$ENTITY_NAME);

        }

        $programmeId = $activity->getCourse()->getProgrammeId();
        $this->log( "Generating Programme Calendar for " . $programmeId );
        $this->calendar->generateProgrammeCalendar( $request, $programmeId );
        $this->log( "Triggered Programme Calendar " . $programmeId );

        return $responseObj;
    }

    #[Post("/group-activities/{groupActivityId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupActivityId  ", description: "id of the group activity to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update a Group Activity. This API endpoint is restricted to coordinators only.")]
    public function updateGroupActivityAction(Request $request, $groupActivityId)
    {
        $this->setLogUuid($request);

        // Find the GroupActivity
        /** @var GroupActivity $groupActivity */
        $groupActivity = $this->findById(self::$ENTITY_NAME, $groupActivityId);

        $this->checkReadWriteAccess($request,$groupActivity->getGroup()->getCourseId());

        $this->processDateActivity($request, $groupActivity);

        if($request->get('location')) {
            $groupActivity->setLocation($request->get('location'));
        }
        if($request->get('published')) {
            $groupActivity->setPublished($request->get('published'));
        }

        $responseObj = $this->update(self::$EMBER_NAME, $groupActivity);

        if($groupActivity->getActivity()->getPublished() == TRUE) {
            // push notifications if published
           
            $this->notify->setLogUuid($request);
            $this->notify->message($groupActivity->getActivity()->getCourse(), self::$ENTITY_NAME);
        }

        $programmeId = $groupActivity->getActivity()->getCourse()->getProgrammeId();
        $this->log( "Generating Programme Calendar for " . $programmeId );
        $this->calendar->generateProgrammeCalendar( $request, $programmeId );
        $this->log( "Triggered Programme Calendar " . $programmeId );

        return $responseObj;
    }

    private function processDateActivity(Request $request, &$groupActivity){
        if($request->get('start_date')) {
            $groupActivity->setStartDate(new \DateTime($request->get('start_date')));
            $groupActivity->setOriginalStartDate($groupActivity->getStartDate());
        }
        if($request->get('end_date')) {
            $groupActivity->setEndDate(new \DateTime($request->get('end_date')));
            $groupActivity->setOriginalEndDate($groupActivity->getEndDate());
        }
    }

    #[Get("/group-activities/{groupActivityId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupActivityId  ", description: "id of the group activity to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a GroupActivity")]
    public function getGroupActivityAction(Request $request, $groupActivityId)
    {
        $this->setLogUuid($request);

        $responseObj = $this->findById(self::$ENTITY_NAME, $groupActivityId);
        return ['group_activity' => $responseObj];
    }

    #[Get("/group-activities")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple GroupActivities")]
    public function getGroupActivitiesAction(Request $request)
    {
        $this->setLogUuid($request);

        $groupActivities = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("GROUP ACTIVITY: ".$id);
            $groupActivities[] = $this->findById(self::$ENTITY_NAME, $id);
        }
        return ['group_activities' => $groupActivities];
    }

    #[Delete("/group-activities/{groupActivityId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupActivityId ", description: "id of the group activity to be deleted", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Group Activity. This API endpoint is restricted to coordinators only.")]
    public function deleteGroupActivityAction(Request $request, $groupActivityId)
    {
        $this->setLogUuid($request);

        $this->log("DELETING GROUP ACTIVITY: " . $groupActivityId);

        /** @var GroupActivity $groupActivity */
        $groupActivity = $this->findById(self::$ENTITY_NAME, $groupActivityId);
        $this->checkReadWriteAccess($request,$groupActivity->getGroup()->getCourseId());

        $wasPublished = $groupActivity->getPublished();

        $activity = $groupActivity->getActivity();

        // Delete Group Activity from database
        $responseObj = $this->deleteById(self::$ENTITY_NAME, $groupActivityId);

        if($activity->getPublished() && $wasPublished) {
            // push notifications if session and group session were published
            $this->notify->setLogUuid($request);
            $this->notify->message($activity->getCourse(), self::$ENTITY_NAME);
        }

        $programmeId = $activity->getCourse()->getProgrammeId();
        $this->log( "Generating Programme Calendar for " . $programmeId );
        $this->calendar->generateProgrammeCalendar( $request, $programmeId );
        $this->log( "Triggered Programme Calendar " . $programmeId );

        return $responseObj;
    }

}
