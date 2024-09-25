<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Entity\Subtask;
use Insead\MIMBundle\Exception\BoxGenericException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Service\File\FileManager;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Task;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Service\Manager\TaskManager;
use Insead\MIMBundle\Service\StudyNotify;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Task")]
class TaskController extends BaseController
{
    public FileManager $fileManager;
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                StudyNotify $notify,
                                EntityManager $em)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->base = $base;

        $s3Object = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $this->fileManager = new FileManager($baseParameterBag->get('study.s3.config'), $logger, $notify, $em, $s3Object, $base);
    }

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Task";

    #[Put("/tasks")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a new Task. This API endpoint is restricted to coordinators only.")]
    public function createTaskAction(Request $request, TaskManager $taskManager)
    {
        $this->setLogUuid($request);

        $courseId = $request->get("course_id");
        $this->checkReadWriteAccess($request,$courseId);

        $paramList = "title,description,date,course_id,published,is_high_priority,high_priority";

        $data = $this->loadDataFromRequest( $request, $paramList );
        $data[ "logUuid" ] = $this->logUuid;

        $responseObj = $taskManager->createTask($data);

        return $responseObj;
    }

    #[Post("/tasks/{taskId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Task. This API endpoint is restricted to coordinators only.")]
    public function updateTaskAction(Request $request, $taskId, TaskManager $taskManager)
    {
        return $taskManager->updateTask($request,$taskId);
    }

    #[Get("/tasks/{taskId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Task. This API endpoint is restricted to coordinators only.")]
    public function getTaskAction(Request $request, $taskId)
    {
        $this->setLogUuid($request);

        $this->log("TASK: ".$taskId);
        $responseObj = $this->findById(self::$ENTITY_NAME, $taskId);
        return [strtolower(self::$ENTITY_NAME) => $responseObj];
    }

    #[Get("/tasks")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Task. This API endpoint is restricted to coordinators only.")]
    public function getTasksAction(Request $request)
    {
        $this->setLogUuid($request);

        $tasks = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("TASK: ".$id);
            array_push($tasks, $this->findById(self::$ENTITY_NAME, $id));
        }
        return ['tasks' => $tasks];
    }

    #[Delete("/tasks/{taskId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "taskId", description: "TaskId", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to delete a Task. This API endpoint is restricted to coordinators only.")]
    public function deleteTaskAction(Request $request, $taskId, StudyNotify $studyNotify)
    {
        $this->setLogUuid($request);

        // get Task
        /** @var Task $task */
        $task = $this->findById(self::$ENTITY_NAME, $taskId);

        /** @var FileManager $s3manager */
        $s3manager = $this->fileManager;
        $prefix = "programme-documents/prog-".$task->getCourse()->getProgrammeId()."/crs-".$task->getCourse()->getId()."/stask-".$task->getId();
        $s3Contents = $s3manager->fetchS3ByPrefix($prefix);
        if ($s3Contents){
            if (!empty($s3Contents['Contents'])) {
                foreach ($s3Contents['Contents'] as $s3ObjectContent) {
                    $fileKeyArray = explode("/",(string) $s3ObjectContent['Key']);
                    array_shift($fileKeyArray); // remove environment name
                    array_shift($fileKeyArray); // remove document-repository
                    $fileToDelete = implode("/",$fileKeyArray);
                    $s3manager->deleteItem($fileToDelete);
                }
            }
        }

        $course = $task->getCourse();
        $isTaskPublished = $task->getPublished();

        $this->checkReadWriteAccess($request,$course->getId());

        // If successfully removed folder from Box, delete from database
        $responseObj = $this->deleteById(self::$ENTITY_NAME, $taskId);

        // push notifications if course & task are published
        if ($course->getPublished() && $isTaskPublished) {
            $notify = $studyNotify;
            $notify->setLogUuid($request);
            $notify->message($course, self::$ENTITY_NAME);
        }
        return $responseObj;
    }

}
