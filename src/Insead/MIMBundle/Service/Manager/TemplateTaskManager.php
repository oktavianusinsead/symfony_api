<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\EntityManager;
use Exception;

use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Task;
use Insead\MIMBundle\Entity\Subtask;
use Insead\MIMBundle\Entity\TemplateSubtask;
use Insead\MIMBundle\Entity\TemplateTask;

use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Exception\BoxGenericException;

use Insead\MIMBundle\Service\File\FileManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class TemplateTaskManager extends Base
{
    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "TemplateTask";
    protected $fileManager;

    public function loadServiceManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Function to retrieve Template Tasks for admin
     *
     * @param Request       $request            Request Object
     *
     * @throws Exception
     *
     * @return Response
     */
    public function getTemplateTasks(Request $request)
    {

        $this->log("Retrieving Template TASKS");

        /** @var TemplateTask $templateTask */
        $templateTasks = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findAll();

        $serializedData = $this->serializer->serialize(["template-tasks" => $templateTasks], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Function to retrieve Template Tasks for admin
     *
     * @param Request       $request            Request Object
     * @param String        $templateTaskId     id of TemplateTask
     *
     * @throws Exception
     *
     * @return Response
     */
    public function getTemplateTask(Request $request,$templateTaskId)
    {

        $this->log("Retrieving Template TASK: " . $templateTaskId);

        /** @var TemplateTask $templateTask */
        $templateTask = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findOneBy(['id' => $templateTaskId]);

        if(!$templateTask) {
            $this->log('TemplateTask not found');
            throw new ResourceNotFoundException('TemplateTask not found');
        }

        $serializedData = $this->serializer->serialize(["template-task" => $templateTask], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    /**
     * Function to copy an existing Task
     *
     * @param Request       $request            Request Object
     * @param String        $taskId             id of the Task
     *
     * @throws Exception
     *
     * @return Response
     */
    public function copyTaskAsTemplate(Request $request, $taskId)
    {

        $this->log("Copying TASK:".$taskId);

        /** @var Task $task */
        $task = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['id' => $taskId]);

        if(!$task) {
            $this->log('Task not found');
            throw new ResourceNotFoundException('Task not found');
        }

        $templateTask = new TemplateTask();
        $templateTask->setSourceTaskId( $task->getId() );
        $templateTask->setDescription( $task->getDescription() );

        if($request->get('title')) {
            $templateTask->setTitle( $request->get('title') );
        } else {
            $templateTask->setTitle( $task->getTitle() );
        }

        $em = $this->entityManager;
        $em->persist($templateTask);
        $em->flush();

        /** @var Subtask $subtask */
        foreach( $task->getSubtasks() as $subtask ) {
            $templateSubtask = new TemplateSubtask();
            $templateSubtask->setTask( $templateTask );

            $templateSubtask->setTitle( $subtask->getTitle() );
            $templateSubtask->setUrl( $subtask->getUrl() );
            $templateSubtask->setPages( $subtask->getPages() );
            $templateSubtask->setSubtaskType( $subtask->getSubtaskType() );
            $templateSubtask->setFilename( $subtask->getFilename() );
            $templateSubtask->setFilesize( $subtask->getFilesize() );
            $templateSubtask->setMimeType( $subtask->getMimeType() );
            $templateSubtask->setEmbeddedContent( $subtask->getEmbeddedContent() );

            $sourcePrefix = "programme-documents/prog-"
                . $task->getCourse()->getProgrammeId()
                . "/crs-"
                . $task->getCourseId()
                . "/stask-"
                . $task->getId()
                . "/";

            $prefix = "template-subtask/" . $templateTask->getId() . "/" . $subtask->getFilename();

            $s3UploadResponse = $this->fileManager->copyExistingItem($prefix, $sourcePrefix . $subtask->getFilename());
            $newId = $s3UploadResponse["data"]["timestamp"];
            $templateSubtask->setBoxId($newId);

            if( !is_null($subtask->getPosition()) ) {
                $templateSubtask->setPosition($subtask->getPosition());
            }

            $em = $this->entityManager;
            $em->persist($templateSubtask);
        }
        $em->flush();

        $templateTaskId = $templateTask->getId();

        /** @var TemplateTask $templateTask */
        $templateTask = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findOneBy(['id' => $templateTaskId]);

        $serializedData = $this->serializer->serialize(["template-task" => $templateTask], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Function to copy an existing Task
     *
     * @param Request       $request            Request Object
     * @param String        $templateTaskId     id of TemplateTask
     *
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return Response
     */
    public function createFromTemplate(Request $request, $templateTaskId)
    {

        $this->log("Copying Template TASK:".$templateTaskId);

        if(!$request->get('date')) {
            throw new InvalidResourceException(["task" => ['Date is a required field']]);
        }
        if(!$request->get('course_id')) {
            throw new InvalidResourceException(["task" => ['Course information was not given']]);
        }

        //find the course
        $course = null;
        if($request->get('course_id')) {
            /** @var Course $course */
            $course = $this->entityManager
                ->getRepository(Course::class)
                ->findOneBy(['id' => $request->get('course_id')]);
        }

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $this->checkReadWriteAccess($request,$course->getId());

        //find the find the template
        /** @var TemplateTask $templateTask */
        $templateTask = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findOneBy(['id' => $templateTaskId]);

        if(!$templateTask) {
            $this->log('TemplateTask not found');
            throw new ResourceNotFoundException('TemplateTask not found');
        }

        //create the task
        $task = new Task();
        $task->setCourse( $course );
        if($request->get('date')) {
            $task->setDate(new \DateTime( $request->get('date') ));
        }

        if($request->get('title')) {
            $task->setTitle( $request->get('title') );
        } else {
            $task->setTitle( $templateTask->getTitle() );
        }
        $task->setDescription( $templateTask->getDescription() );

        if($request->get('published') === true) {
            $task->setPublishedAt(new \DateTime());
            $task->setPublished(true);
        }

        $em = $this->entityManager;
        $em->persist($task);
        $em->flush();

        /** @var TemplateSubtask $templateSubtask */
        foreach( $templateTask->getTemplateSubtasks() as $templateSubtask ) {
            $subtask = new Subtask();
            $subtask->setTask( $task );

            $subtask->setTitle( $templateSubtask->getTitle() );
            $subtask->setUrl( $templateSubtask->getUrl() );
            $subtask->setPages( $templateSubtask->getPages() );
            $subtask->setSubtaskType( $templateSubtask->getSubtaskType() );
            $subtask->setFilename( $templateSubtask->getFilename() );
            $subtask->setFilesize( $templateSubtask->getFilesize() );
            $subtask->setMimeType( $templateSubtask->getMimeType() );
            $subtask->setEmbeddedContent( $templateSubtask->getEmbeddedContent() );

            $prefix = "programme-documents/prog-"
                . $task->getCourse()->getProgrammeId()
                . "/crs-"
                . $task->getCourseId()
                . "/stask-"
                . $task->getId()
                . "/";

            $sourcePrefix = "template-subtask/" . $templateTask->getId() . "/" . $templateSubtask->getFilename();
            $this->log("Copying subtask file from: $sourcePrefix to ".$prefix . $templateSubtask->getFilename());
            $s3UploadResponse = $this->fileManager->copyExistingItem($sourcePrefix,$prefix . $templateSubtask->getFilename());
            $newId = $s3UploadResponse["data"]["timestamp"];
            $subtask->setBoxId($newId);
            $subtask->setUploadToS3(1);
            $subtask->setAwsPath($prefix);
            $subtask->setFileId($newId);

            if( !is_null($templateSubtask->getPosition()) ) {
                $subtask->setPosition( $templateSubtask->getPosition() );
            }

            $em = $this->entityManager;
            $em->persist($subtask);
        }
        $em->flush();

        $taskId = $task->getId();

        /** @var Task $task */
        $task = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['id' => $taskId]);

        $serializedData = $this->serializer->serialize(["task" => $task], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Function to update an existing Task
     *
     * @param Request       $request            Request Object
     * @param String        $templateTaskId     id of TemplateTask
     *
     * @throws Exception
     *
     * @return Response
     */
    public function updateTemplateTask(Request $request, $templateTaskId)
    {

        $this->log("TASK:".$request->get('title'));

        /* @var $task TemplateTask */
        $task = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findOneBy(['id' => $templateTaskId]);

        if(!$task) {
            $this->log('Task not found');
            throw new ResourceNotFoundException('Task not found');
        }

        // Set new values for Task
        if($request->get('title')) {
            $task->setTitle($request->get('title'));
        }
        if($request->get('description')) {
            $task->setDescription($request->get('description'));
        }
        //cannot use $request->get cause is standard can get a value false
        if( $request->request->has('is_standard')) {
            $task->setStandard($request->get('is_standard'));
        }

        $responseobj = $this->updateRecord(self::$ENTITY_NAME, $task);

        return $responseobj;
    }

    /**
     * Function to delete Template Tasks for admin
     *
     * @param Request       $request            Request Object
     * @param String        $templateTaskId          id of the TemplateSubTask
     *
     * @throws Exception
     *
     * @return Response
     */
    public function deleteTemplateTask(Request $request, $templateTaskId)
    {
        $this->setLogUuid($request);

        $this->log('REMOVING TEMPLATE TASK: ' . $templateTaskId);

        // get task
        /** @var Task $task */
        $task = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findOneBy(['id' => $templateTaskId]);

        if(!$task) {
            $this->log('TemplateTask not found');
            throw new ResourceNotFoundException('TemplateTask not found');
        }

        $em = $this->entityManager;
        $em->remove($task);
        $em->flush();

        /** @var TemplateTask $templateTask */
        $templateTasks = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findAll();

        $serializedData = $this->serializer->serialize(["template-tasks" => $templateTasks], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Function to create Template Tasks for admin
     *
     * @param Request       $request            Request Object
     *
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return Response
     */
    public function newTemplateTask(Request $request)
    {
        $this->setLogUuid($request);

        $paramList = "title,description,is_standard";

        $data = $this->loadDataFromRequest( $request, $paramList );

        $title = $data['title'];
        $description = $data['description'];
        $is_standard = $data['is_standard'];

        $this->log( "Creating template task:" . $title );

        $templateTask = new TemplateTask();
        $templateTask->setTitle($title);
        $templateTask->setDescription($description);
        $templateTask->setStandard($is_standard);

        try {
            $this->validateObject($templateTask);

            $em = $this->entityManager;
            $em->persist($templateTask);
            $em->flush();

            $this->log("Initial Template Task Information saved.");
        } catch (Exception $e) {
            $this->log("Error " . $e->getCode() . ": " . $e->getMessage());
            throw $e;
        }

        $templateTask->setBoxFolderId("S3-".mktime(date("H")));

        try {
            $this->validateObject($templateTask);

            $em->persist($templateTask);
            $em->flush();

        } catch (Exception $e) {
            $this->log("Error " . $e->getCode() . ": " . $e->getMessage());
            throw $e;
        }

        $templateTask_saved = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findOneBy(['id' => $templateTask->getId()]);

        $serializedData = $this->serializer->serialize(["template-task" => $templateTask_saved], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }

}
