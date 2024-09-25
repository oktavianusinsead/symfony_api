<?php

namespace Insead\MIMBundle\Controller;

use Exception;

use Insead\MIMBundle\Entity\CourseSubscription;
use Insead\MIMBundle\Entity\Subtask;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Insead\MIMBundle\Exception\PermissionDeniedException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Exception\ConflictFoundException;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Entity\UserSubtask;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserSubtaskController extends BaseController
{
    /**
     *  User Table
     *  @var string
     *  Name of the Entity
     */
    public static string $ENTITY_NAME = "User";

    public static array $PREFERRED_EMAIL_ENUM = ['0' => 'HOME', '1' => 'BUSN'];

    public static array $PREFERRED_PHONE_ENUM = ['0' => 'HOME', '1' => 'BUSN', '2' => 'BMOB'];
    public static array $DOCUMENT_TYPE = ['file_document', 'linked_document', 'video', 'link'];

    /**
     * @throws ConflictFoundException
     * @throws PermissionDeniedException
     * @throws ResourceNotFoundException
     */
    #[Post("/profile/completed-subtasks/{subtaskId}")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "subtaskId ", description: "SubtaskId to be marked", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to mark a subtask as complete.")]
    public function updateCompleteSubtaskAction(Request $request,$subtaskId)
    {
        $this->setLogUuid($request);

        /** @var Subtask $subtask */
        $subtask = $this->findById('Subtask', $subtaskId);

        // check if Subtask exist
        if (!$subtask) {
            $this->log("Invalid SubtaskId: $subtaskId");
            throw new ResourceNotFoundException();
        }

        $course = $subtask->getTask()->getCourse();

        // check if user is allowed to access this course subtask
        if (!$this->userHasCourseAccess($request,$course->getId())) {
            throw new PermissionDeniedException('User is not allowed to access this subtask');
        }

        $userSubtask = new UserSubtask();
        $userSubtask->setUser($this->getCurrentUserObj($request));
        $userSubtask->setSubtask($subtask);
        $userSubtask->setCourse($course);

        try {
            $this->create('UserSubtask', $userSubtask);
        } catch (Exception) {
            throw new ConflictFoundException('Subtask was already added.');
        }

        return $this->getUserSubtasks($request);
    }

    /**
     * @throws ResourceNotFoundException
     */
    #[Delete("/profile/completed-subtasks/{subtaskId}")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "subtaskId ", description: "SubtaskId to be marked", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to unmark a subtask as complete.")]
    public function deleteCompleteSubtaskAction(Request $request,$subtaskId)
    {
        $this->setLogUuid($request);

        // check if subtask is in the user subtasks lists
        $criteria = array_filter(['user' => $this->getCurrentUserObj($request), 'subtask' => $this->findById('Subtask', $subtaskId)]);

        $userSubtaskCollection   = $this->findBy('UserSubtask', $criteria);

        /** @var UserSubtask $userSubtask */
        $userSubtask = $userSubtaskCollection[0];
        $userSubtaskId = $userSubtask->getId();

        return $this->deleteById('UserSubtask', $userSubtaskId);
    }

    /**
     * @throws ResourceNotFoundException
     */
    #[Get("/profile/completed-subtasks")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Parameter(name: "request", description: "expect GET parameter 'course' to filter subtask", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get completed task.")]
    public function getCompleteSubtaskAction(Request $request)
    {
        $this->setLogUuid($request);

        $courseId = $request->get('course');

        if ($courseId) {
            $criteria = array_filter(['user' => $this->getCurrentUserObj($request), 'course' => $this->findById('Course', $courseId)]);
        } else {
            $criteria = array_filter(['user' => $this->getCurrentUserObj($request)]);
        }

        try {
            $userSubtasks = $this->findBy('UserSubtask', $criteria);
        } catch (ResourceNotFoundException) {}

        //return ids
        $ids = [];

        if (isset($userSubtasks)) {
            /** @var UserSubtask $userSubtask */
            foreach ($userSubtasks as $userSubtask) {
                $ids[] = (int)$userSubtask->getSubtask()->getId();
            }
        }

        return [ 'subtasks' => $ids ];

    }

    /**
     * Function to return UserSubtasks
     * @return array
     * @throws ResourceNotFoundException
     */
    private function getUserSubtasks(Request $request)
    {
        $userSubtasks = $this->doctrine->getRepository(UserSubtask::class)->findBy(['user' => $this->getCurrentUserObj($request)]);
        $result = ['subtasks'=>[]];

        foreach ($userSubtasks as $userSubtask) {
            $result['subtasks'][] = (int)$userSubtask->getSubtask()->getId();
        }

        return $result;
    }

    /**
     * Checks is a user has access to Course requested
     *
     * @param int $courseId Requesting Course Id
     *
     * @return bool
     * @throws ResourceNotFoundException
     */
    private function userHasCourseAccess(Request $request,$courseId)
    {
        $courseUserRole = $this->doctrine->getRepository(CourseSubscription::class)->findBy(['user' => $this->getCurrentUserObj($request), 'course' => $courseId]);

        return count($courseUserRole)>0;
    }

}
