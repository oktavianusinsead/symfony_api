<?php

namespace Insead\MIMBundle\Controller;

use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Group;
use Insead\MIMBundle\Entity\GroupActivity;
use Insead\MIMBundle\Entity\GroupSession;

use Insead\MIMBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use Insead\MIMBundle\Attributes\Allow;

use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Exception\ConflictFoundException;
use Doctrine\Persistence\ManagerRegistry;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Group")]
class GroupController extends BaseController
{

    /**
     * @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Group";

    /**
     * @var array()
     *
     */
    public static $COLOUR_ENUM = [
        '0' => 'red',
        '1' => 'purple',
        '2' => 'blue',
        '3' => 'yellow',
        '4' => 'green',
        '5' => 'chocolate'
    ];

    #[Put("/groups")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to create a new Group. This API endpoint is restricted to coordinators only.")]
    public function createGroupAction(Request $request)
    {
        $this->setLogUuid($request);

        //Check if Course exists
        /** @var Course $course */
        $course = $this->findById('Course', $request->get('course_id'));

        $this->checkReadWriteAccess($request,$course->getId());

        $group = new Group();
        $group->setCourse($course);
        $group->setName($request->get('name') );
        if($request->get('start_date')) {
            $group->setStartDate(new \DateTime($request->get('start_date')));
        }
        if($request->get('end_date')) {
            $group->setEndDate(new \DateTime($request->get('end_date')));
        }

        $group->setColour($request->get('colour'));
        $group->setPsStdntGroup($request->get('ps_stdnt_group'));
        $group->setPsDescr($request->get('ps_descr'));
        $group->setCourseDefault(FALSE);

        $responseObj = $this->create(self::$ENTITY_NAME, $group);
        return $responseObj;
    }

    #[Post("/groups/{groupId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupId ", description: "id of the group to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Group. This API endpoint is restricted to coordinators only.")]
    public function updateGroupAction(Request $request, $groupId)
    {
        $this->setLogUuid($request);

        $this->log("UPDATING GROUP:" . $groupId);

        $this->validateRelationshipUpdate('course_id', $request);

        // Find the Group
        /** @var Group $group */
        $group = $this->findById(self::$ENTITY_NAME, $groupId);

        $this->checkReadWriteAccess($request,$group->getCourseId());

        if($request->get('name')) {
            $group->setName($request->get('name'));
        }
        if($request->get('start_date')) {
            $group->setStartDate(new \DateTime($request->get('start_date')));
        }
        if($request->get('end_date')) {
            $group->setEndDate(new \DateTime($request->get('end_date')));
        }

        if( !is_null($request->get('colour')) ) {
            $group->setColour($request->get('colour'));
        }
        if($request->get('ps_stdnt_group')) {
            $group->setPsStdntGroup($request->get('ps_stdnt_group'));
        }
        if($request->get('ps_descr')) {
            $group->setPsDescr($request->get('ps_descr'));
        }

        $responseObj = $this->update(self::$ENTITY_NAME, $group);
        return $responseObj;
    }

    #[Get("/groups/{groupId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupId ", description: "id of the group to be retrieved", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Group.")]
    public function getGroupAction(Request $request, $groupId)
    {
        $this->setLogUuid($request);

        $scope  = $this->getCurrentUserScope($request);
        $user   = $this->getCurrentUserObj($request);

        /** @var Group $group */
        $group = $this->findById(self::$ENTITY_NAME, $groupId);

        //check if user has access to the programme
        $programme = $group->getCourse()->getProgramme();
        $programme->setRequestorId($user->getId());
        $programme->setRequestorScope($scope);

        return [strtolower(self::$ENTITY_NAME) => $group];
    }

    #[Get("/groups")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Groups.")]
    public function getGroupsAction(Request $request)
    {
        $this->setLogUuid($request);

        $scope  = $this->getCurrentUserScope($request);
        $user   = $this->getCurrentUserObj($request);

        $groups = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("GROUP: ".$id);

            /** @var Group $group */
            $group = $this->findById(self::$ENTITY_NAME, $id);

            //check if user has access to the programme
            $programme = $group->getCourse()->getProgramme();
            $programme->setRequestorId($user->getId());
            $programme->setRequestorScope($scope);

            array_push($groups, $group);
        }
        return ['groups' => $groups];
    }

    #[Delete("/groups/{groupId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupId  ", description: "id of the group to be deleted", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Group. This API endpoint is restricted to coordinators only.")]
    public function deleteGroupAction(Request $request, $groupId)
    {
        $this->setLogUuid($request);

        $this->log("DELETING GROUP: " . $groupId);

        // Check if the group is Course's default
        /** @var Group $group */
        $group = $this->findById(self::$ENTITY_NAME, $groupId);
        if($group) {
            if($group->getCourseDefault()) {
                throw new InvalidResourceException(['group_id' => ['Default Section of a course cannot be deleted.']]);
            }
        }

        $this->checkReadWriteAccess($request,$group->getCourseId());

        // Check if this Group has Published Scheduled Sessions
        if(count($group->getGroupSessions()) > 0) {
            $hasPublishedSchedules = FALSE;
            /** @var GroupSession $groupSession */
            foreach($group->getGroupSessions() as $groupSession) {
                if($groupSession->getPublished()) {
                    $hasPublishedSchedules = TRUE;
                    break;
                }
            }
            if($hasPublishedSchedules) {
                throw new ConflictFoundException('This Section has some published Sessions scheduled. You cannot delete this Section.');
            }
        }

        // Check if this Group has Published Scheduled Activities
        if(count($group->getGroupActivities()) > 0) {
            $hasPublishedSchedules = FALSE;
            /** @var GroupActivity $groupActivity */
            foreach($group->getGroupActivities() as $groupActivity) {
                if($groupActivity->getPublished()) {
                    $hasPublishedSchedules = TRUE;
                    break;
                }
            }
            if($hasPublishedSchedules) {
                throw new ConflictFoundException('This Section has some published Activities scheduled. You cannot delete this Section.');
            }
        }

        // Delete Group from database
        return $this->deleteById(self::$ENTITY_NAME, $groupId);
    }

    #[Post("/groups/{groupId}/people")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupId  ", description: "id of the group to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Assign people to a Group.")]
    public function assignPeopleToGroupAction(Request $request, $groupId)
    {
        $this->setLogUuid($request);

        $this->log("Assigning People to Group:".$groupId);

        /** @var Group $group */
        $group = $this->findById(self::$ENTITY_NAME, $groupId);

        $this->checkReadWriteAccess($request,$group->getCourseId());

        $people = $request->get('people');

        $em = $this->doctrine->getManager();

        foreach($people as $psoftid) {
            //Check if this person is already registered to the course
            //Find user by Peoplesoft ID
            $user = $this->doctrine
                              ->getRepository(User::class)
                              ->findOneBy(['peoplesoft_id' => $psoftid]);

            //Assign user to group
            if ($user) {
                $this->log("USER FOUND: " . json_encode($user));
                //Assign professor to session
                $group = $group->addUser($user);

                $em->persist($group);

                $this->log("USER ASSIGNED TO GROUP: " . $psoftid);
            } else {
                $this->log('USER: ' . $psoftid . ' NOT FOUND');
                throw new ResourceNotFoundException('USER: ' . $psoftid . ' NOT FOUND');
            }
        }

        $em->flush();

        return ['students' => $group->getUsersList()];

    }

    #[Delete("/groups/{groupId}/people/{peoplesoftId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "groupId  ", description: "id of the group to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "peoplesoftId   ", description: "peoplesoftid of the user that would be removed from the group", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Remove User from a Group.")]
    public function removeUserFromGroupAction(Request $request, $groupId, $peoplesoftId)
    {
        $this->setLogUuid($request);

        $this->log("Removing People to Group:".$groupId);
        /** @var Group $group */
        $group = $this->findById(self::$ENTITY_NAME, $groupId);

        $this->checkReadWriteAccess($request,$group->getCourseId());

        //Find user by Peoplesoft ID
        $user = $this->doctrine
                          ->getRepository(User::class)
                          ->findOneBy(['peoplesoft_id' => $peoplesoftId]);

        if($user) {
            //Un-Assign user from session
            $group = $group->removeUser($user);
            $em = $this->doctrine->getManager();
            $em->persist($group);
            $em->flush();
        } else {
                $this->log('USER: ' . $peoplesoftId . ' NOT FOUND');
                throw new ResourceNotFoundException('USER: ' . $peoplesoftId . ' NOT FOUND');
            }
        return ['students' => $group->getUsersList()];
    }
}
