<?php

namespace esuite\MIMBundle\Service\Manager;

use Exception;

use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Task;

use esuite\MIMBundle\Exception\BoxGenericException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class TaskManager extends Base
{

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Task";

    /**
     * Function to create a Task
     *
     * @param array         @data               an array of data to be passed to the function
     *
     * @throws BoxGenericException
     * @throws InvalidResourceException
     * @throws Exception
     * @throws ResourceNotFoundException
     *
     * @return Response
     */
    public function createTask($data)
    {
        $logUuid            = $data['logUuid'];
        $title              = $data['title'];
        $description        = $data['description'];
        $date               = $data['date'];
        $course_id          = $data['course_id'];
        $isPublished        = $data['published'];
        $highPriority       = $data['is_high_priority'];
        $markedHighPriority = $data['high_priority'];

        $this->setLogUuid($logUuid);
        $this->notify->setLogUuidWithString($logUuid);

        $this->log( "Tasks:" . $title );
        $this->log( "Creating Tasks for course_id: " . $course_id );

        /* @var $course Course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $course_id]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $task = new Task();
        $task->setCourse($course);
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setHighPriority($highPriority);
        $task->setMarkedHighPriority($markedHighPriority);

        if($date) {
            $task->setDate(new \DateTime($date));
        }

        if($isPublished === true) {
            $task->setPublishedAt(new \DateTime());
        }
        $task->setPublished($isPublished);

        $this->validateObject($task);

        //save the task information first so that we would have an ID
        try {
            $this->validateObject($task);

            $em = $this->entityManager;
            $em->persist($task);
            $em->flush();

            $this->log("Initial Task Information saved.");
        } catch (Exception $e) {
            $this->log("Error " . $e->getCode() . ": " . $e->getMessage());
            throw $e;
        }

        $task->setBoxFolderId("S3-".mktime(date("H")));

        try {
            $keyName = strtolower(self::$ENTITY_NAME);

            $this->validateObject($task);

            $em->persist($task);
            $em->flush();

        } catch (Exception $e) {
            $this->log("Error " . $e->getCode() . ": " . $e->getMessage());
            throw $e;
        }

        $taskObj = [$keyName => $task];

        $serializedData = $this->serializer->serialize($taskObj, 'json');

        $responseObj = new Response($serializedData);
        $responseObj->setStatusCode(201);
        $responseObj->headers->set('Content-Type', 'application/json');

        if ($isPublished) {
            $this->notify->message($course, self::$ENTITY_NAME, $task->getTitle());
        }

        return $responseObj;
    }

    /**
     * Function to update an existing Task
     *
     * @param Request       $request            Request Object
     * @param String        $taskId             id of the Task
     *
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return Response
     */
    public function updateTask(Request $request, $taskId)
    {
        $this->log("TASK:".$request->get('title'));

        $this->validateRelationshipUpdate('course_id', $request);


        /* @var $task Task */
        $task = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['id' => $taskId]);

        if(!$task) {
            $this->log('Task not found');
            throw new ResourceNotFoundException('Task not found');
        }

        $this->checkReadWriteAccess($request,$task->getCourseId());


        $wasPublished = $task->getPublished();

        // Set new values for Task
        if($request->get('title')) {
            $task->setTitle($request->get('title'));
        }
        if($request->get('description')) {
            $cleanedDesc = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $request->get('description'));
            $cleanedDesc = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $cleanedDesc);
            $cleanedDesc = preg_replace('#<link(.*?)>(.*?)</link>#is', '', $cleanedDesc);

            //removing inline js events
            $cleanedDesc = preg_replace("/([ ]on[a-zA-Z0-9_-]{1,}=\".*\")|([ ]on[a-zA-Z0-9_-]{1,}='.*')|([ ]on[a-zA-Z0-9_-]{1,}=.*[.].*)/","", $cleanedDesc);

            //removing inline js
            $cleanedDesc = preg_replace("/([ ]href.*=\".*javascript:.*\")|([ ]href.*='.*javascript:.*')|([ ]href.*=.*javascript:.*)/i","", $cleanedDesc);
            $task->setDescription($cleanedDesc);
        }
        if($request->get('published')) {
            $task->setPublished($request->get('published'));
        } else {
            if( $request->get('published') === false ) {
                $task->setPublished(false);
            }
        }

        if($request->get('date')) {
            $task->setDate(new \DateTime($request->get('date')));
        } else {
            if ($request->request->has('date')) {
                $task->setDate(null);
            }
        }

        if($request->get('published')) {
            $task->setPublishedAt(new \DateTime());
        }

        if($request->request->has('position')) {
            $task->setPosition((int)$request->get('position'));
        }

        if($request->request->has('is_high_priority')) {
            $task->setHighPriority((int)$request->get('is_high_priority'));
        }

        if($request->request->has('high_priority')) {
            $task->setMarkedHighPriority((int)$request->get('high_priority'));
        }

        if($request->request->has('is_archived')) {
            $task->setArchived((boolean)$request->get('is_archived'));
        }

        $responseObj = $this->updateRecord(self::$ENTITY_NAME, $task);

        // push notifications if published
        if ($request->get('published') && !$wasPublished) {
            $this->notify->setLogUuid($request);
            $this->notify->message($task->getCourse(), self::$ENTITY_NAME, $task->getTitle());
        }

        return $responseObj;
    }

}
