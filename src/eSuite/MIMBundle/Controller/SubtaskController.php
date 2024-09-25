<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use esuite\MIMBundle\Service\File\FileManager;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Task;
use esuite\MIMBundle\Entity\Subtask;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\edotCourseBackup;
use esuite\MIMBundle\Service\edotNotify;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Subtask")]
class SubtaskController extends BaseController
{
    public FileManager $fileManager;
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                edotNotify $notify,
                                EntityManager $em)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->base = $base;

        $s3Object = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $this->fileManager = new FileManager($baseParameterBag->get('edot.s3.config'), $logger, $notify, $em, $s3Object, $base);
    }

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Subtask";

    #[Post("/subtasks")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a new Subtask. This API endpoint is restricted to coordinators only.")]
    public function createSubtaskAction(Request $request, edotNotify $edotNotify, edotCourseBackup $edotCourseBackup)
    {
        $this->setLogUuid($request);
        $resetBackup = FALSE;

        $contentType = $request->headers->get('Content-Type');

        /** @var FileManager $s3manager */
        $s3manager = $this->fileManager;

        // decode metadata
        if (strrpos((string) $contentType, 'application/json') === 0) {
            $data = [
                'task_id' => $request->get('task_id'),
                'title' => $request->get('title'),
                'subtask_type' => $request->get('subtask_type'),
                'url' => $request->get('url'),
                'email_send_to'=> $request->get('email_send_to'),
                'email_subject'=> $request->get('email_subject'),
                'embedded_content'=> $request->get('embedded_content')
            ];

        } else {

            // assume 'multipart/form-data'
            $data = json_decode((string) $request->get('data'), true);
        }



        // If Subtask Type is Link
        if($data['subtask_type'] == '1') {
            if($data['url'] === null || $data['url'] == '') {
                throw new InvalidResourceException(['subtask' => ['Please enter a URL.']]);
            }
        }

        // If Subtask Type is File
        if($data['subtask_type'] == '0') {
            if($request->files->get('file') === null || $request->files->get('file') == '') {
                throw new InvalidResourceException(['subtask' => ['Please upload a file.']]);
            }

            $resetBackup = TRUE;
        }

        // Get file
        $uploadedFile = $request->files->get('file');

        // find parent Task
        /** @var Task $task */
        $task = $this->findById('Task', $data[ 'task_id' ]);

        $this->checkReadWriteAccess($request,$task->getCourseId());

        $subtask = new Subtask();
        $subtask->setTask($task);
        $subtask->setTitle($data[ 'title' ]);
        $subtask->setSubtaskType($data[ 'subtask_type' ]);

        if ($data['subtask_type'] === 1) {
            $subtask->setUrl($this->customUrlEncode($data['url']));
        }

        if ($data['subtask_type'] == '3') {
            $emailaddresses = explode(';', (string) $data[ 'email_send_to' ]);

            $errorMessages=[];


            foreach($emailaddresses as $emailaddress){
                if(!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,8}$/ix",trim($emailaddress)))
                {   $errorField = "error";
                        $errorField = str_replace('[','',$errorField);
                        $errorField = str_replace(']','',$errorField);
                        $errorMsg = "Email is not correct : ".$emailaddress;
                        array_push( $errorMessages,$errorMsg);
                }
            }

            if ( count($errorMessages) > 0 ) {
                $this->log("Error occurred during SubTask, Email Adding: " . json_encode($errorMessages));
                throw new InvalidResourceException($errorMessages);
            }

            $subtask->setEmailSendTo($data[ 'email_send_to' ]);
            $subtask->setEmailSubject($data[ 'email_subject' ]);

        }

        $prefix = "programme-documents/prog-"
            . $task->getCourse()->getProgrammeId()
            . "/crs-"
            . $task->getCourseId()
            . "/stask-"
            . $task->getId()
            . "/";

        if ($uploadedFile !== null) {

            if ($s3manager->checkItemExist($prefix . $uploadedFile->getClientOriginalName())) {
                throw new InvalidResourceException(['file_document' => ['File already exists.']]);
            }

            $result = $s3manager->processS3File($prefix, 'Task', $uploadedFile);
            $s3UploadResponse = $result["response"];
            $fileId = $s3UploadResponse["data"]["timestamp"];

            $subtask->setBoxId($fileId);
            $subtask->setFilesize($result["filesize"]);
            $subtask->setFilename($result["filename"]);
            $subtask->setMimeType($result["filetype"]);
            $subtask->setUploadToS3(1);
            $subtask->setAwsPath($prefix);
            $subtask->setFileId($s3UploadResponse["data"]["timestamp"]);
            if (isset($result["pages"])) {
                $subtask->setPages(count($result["pages"]));
            }
        }

        try {

            if ($data['embedded_content']){
                $subtask->setEmbeddedContent($data['embedded_content']);
            }

            $responseObj = $this->create(self::$ENTITY_NAME, $subtask);
        } catch (Exception $e) {
            if ($uploadedFile !== null) {
                $this->log('*** Create Subtask Failed. Removing file from Box !');
                if($data['subtask_type'] == '0') {
                    $this->fileManager->deleteItem($prefix . $result["filename"]);
                }
                throw new InvalidResourceException(['subtask' => ['Error while creating a Subtask: '.$e->getMessage()]]);
            }
            throw $e;
        }

        // Push notifications
        if ($task->getPublished()) {
            $notify = $edotNotify;
            $notify->setLogUuid($request);
            $notify->message($task->getCourse(), self::$ENTITY_NAME);
        }

        if ($resetBackup && ($task->getCourse()->getPublished() == TRUE) && ($task->getCourse()->getProgramme()->getPublished() == TRUE)) {
            $this->log("Needs a Backup reset.");
            // Update Course Backup
            $backupService = $edotCourseBackup;
            $backupService->updateCoursebackup($task->getCourse());
        }

        return $responseObj;
    }

    #[Post("/subtasks/{subtaskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "subtaskId", description: "SubtaskId", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Subtask. This API endpoint is restricted to coordinators only.")]
    public function updateSubtaskAction(Request $request, $subtaskId, edotNotify $edotNotify)
    {
        $this->setLogUuid($request);

        $this->log("SUBTASK:".$request->get('title'));

        $this->validateRelationshipUpdate('task_id', $request);

        // Find the Subtask
        /** @var Subtask $subtask */
        $subtask = $this->findById(self::$ENTITY_NAME, $subtaskId);

        $this->checkReadWriteAccess($request,$subtask->getTask()->getCourseId());

        // Set new values for Subtask
        if($request->get('title')) {
            $subtask->setTitle($request->get('title'));
        }
        if( !is_null($request->get('subtask_type')) ) {
            $subtask->setSubtaskType($request->get('subtask_type'));
        }
        if($request->get('url')) {
            $subtask->setUrl($request->get('url'));
        }
        if($request->get('embedded_content')) {
            $subtask->setEmbeddedContent($request->get('embedded_content'));
        }
       
        if($request->request->has('position')) {
            $subtask->setPosition( (int)$request->get('position'));
        }
        if(!is_null($request->get('email_send_to'))){
            $emailaddresses = explode(';', (string) $request->get('email_send_to'));

            $errorMessages=[];


            foreach($emailaddresses as $emailaddress){
                if(!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,8}$/ix",trim($emailaddress)))
                {   $errorField = "error";
                    $errorField = str_replace('[','',$errorField);
                    $errorField = str_replace(']','',$errorField);
                    $errorMsg = "Email is not correct : ".$emailaddress;
                    array_push( $errorMessages,$errorMsg);
                }
            }

            if ( count($errorMessages) > 0 ) {
                $this->log("Error occurred during SubTask, Email Adding: " . json_encode($errorMessages));
                throw new InvalidResourceException($errorMessages);
            }
            $subtask->setEmailSendTo($request->get('email_send_to'));
        }
        if(!is_null($request->get('email_subject'))){

            $subtask->setEmailSubject($request->get('email_subject'));
        }

        $responseObj = $this->update(self::$ENTITY_NAME, $subtask);

        // Push notifications
        if($subtask->getTask()->getPublished()) {
            $notify = $edotNotify;
            $notify->setLogUuid($request);
            $notify->message($subtask->getTask()->getCourse(), self::$ENTITY_NAME);
        }
        return $responseObj;

    }

    #[Get("/subtasks/{subtaskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "subtaskId", description: "SubtaskId", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Subtask. This API endpoint is restricted to coordinators only.")]
    public function getSubtaskAction(Request $request, $subtaskId)
    {
        $this->setLogUuid($request);

        $this->log("SUBTASK: ".$subtaskId);
        $responseObj = $this->findById(self::$ENTITY_NAME, $subtaskId);
        return [strtolower(self::$ENTITY_NAME) => $responseObj];
    }

    #[Get("/subtasks")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Subtask. This API endpoint is restricted to coordinators only.")]
    public function getSubtasksAction(Request $request)
    {
        $this->setLogUuid($request);

        $subtasks = [];
        $ids = $request->get('ids');
        foreach ($ids as $id) {
            array_push($subtasks, $this->findById(self::$ENTITY_NAME, $id));
        }
        return ['subtasks' => $subtasks];
    }

    #[Delete("/subtasks/{subtaskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "subtaskId", description: "SubtaskId", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Subtask. This API endpoint is restricted to coordinators only.")]
    public function deleteSubtaskAction(Request $request, $subtaskId, edotNotify $edotNotify, edotCourseBackup $edotCourseBackup)
    {
        $this->setLogUuid($request);

        $resetBackup = FALSE;

        $this->log('REMOVING SUBTASK: ' . $subtaskId);

        // get Subtask
        /** @var Subtask $subtask */
        $subtask = $this->findById(self::$ENTITY_NAME, $subtaskId);

        $task = $subtask->getTask();
        $course = $subtask->getTask()->getCourse();

        $this->checkReadWriteAccess($request,$course->getId());

        // remove from Box
        if ($subtask->getSubtaskType() == 0) {

            // remove from s3
            if ($subtask->getUploadToS3() == '1') {
                $prefix = "programme-documents/prog-"
                    . $task->getCourse()->getProgrammeId()
                    . "/crs-"
                    . $task->getCourseId()
                    . "/stask-"
                    . $task->getId()
                    . "/";

                $path = $prefix . $subtask->getFilename();
                $this->fileManager->deleteItem($path);
            }

            $resetBackup = TRUE;
        }
        $responseObj = $this->deleteById(self::$ENTITY_NAME, $subtaskId);

        // Push notifications
        if($task->getPublished()) {
            $notify = $edotNotify;
            $notify->setLogUuid($request);
            $notify->message($course, self::$ENTITY_NAME);
        }

        if ($resetBackup && ($task->getCourse()->getPublished() == TRUE) && ($task->getCourse()->getProgramme()->getPublished() == TRUE)) {
            $this->log("Needs a Backup reset.");
            // Update Course Backup
            $backupService = $edotCourseBackup;
            $backupService->updateCoursebackup($task->getCourse());
        }

        return $responseObj;
    }
}
