<?php

namespace esuite\MIMBundle\Service\Manager;

use Doctrine\ORM\EntityManager;
use Exception;

use esuite\MIMBundle\Entity\TemplateSubtask;
use esuite\MIMBundle\Entity\TemplateTask;

use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;

use esuite\MIMBundle\Service\File\FileManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class TemplateSubtaskManager extends Base
{

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "TemplateSubtask";
    protected $fileManager;

    public function loadServiceManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Function to retrieve Template Sub Tasks for admin
     *
     * @param Request       $request            Request Object
     *
     * @throws Exception
     *
     * @return Response
     */
    public function getTemplateSubtasks(Request $request)
    {

        $this->log("Retrieving Template SUBTASKS");

        /** @var TemplateSubtask $templateSubtasks */
        $templateSubtasks = $this->entityManager
            ->getRepository(TemplateSubtask::class)
            ->findAll();

        $serializedData = $this->serializer->serialize(["template-subtasks" => $templateSubtasks], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Function to retrieve Template Sub Tasks for admin
     *
     * @param Request       $request            Request Object
     * @param String        $templateSubtaskId     id of TemplateTask
     *
     * @throws Exception
     *
     * @return Response
     */
    public function getTemplateSubtask(Request $request,$templateSubtaskId)
    {

        $this->log("Retrieving Template SUBTASK: " . $templateSubtaskId);

        /** @var TemplateTask $templateTask */
        $templateSubtask = $this->entityManager
            ->getRepository(TemplateSubtask::class)
            ->findOneBy(['id' => $templateSubtaskId]);

        if(!$templateSubtask) {
            $this->log('TemplateSubtask not found');
            throw new ResourceNotFoundException('TemplateSubtask not found');
        }

        $serializedData = $this->serializer->serialize(["template-subtask" => $templateSubtask], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Function to add new Template Sub Tasks for admin
     *
     * @param Request       $request            Request Object
     *
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return Response
     */
    public function newTemplateSubtask(Request $request)
    {

        $contentType = $request->headers->get('Content-Type');

        $paramList = "template_task_id,title,subtask_type,url,embedded_content";

        // decode metadata
        if (strrpos((string) $contentType, 'application/json') === 0) {
            $data = $this->loadDataFromRequest( $request, $paramList );
        } else {
            // assume 'multipart/form-data'
            $data = json_decode((string) $request->get('data'), true);
        }

        $task_id = $data['template_task_id'];
        $title = $data['title'];
        $subtask_type = $data['subtask_type'];
        $url = $data['url'];

        // If Subtask Type is Link
        if($subtask_type == '1') {
            if($url === null || $url == '') {
                throw new InvalidResourceException(['subtask' => ['Please enter a URL.']]);
            }
        }

        // If Subtask Type is File
        if($subtask_type == '0') {
            if($request->files->get('file') === null || $request->files->get('file') == '') {
                throw new InvalidResourceException(['subtask' => ['Please upload a file.']]);
            }
        }

        // Get file
        $uploadedFile = $request->files->get('file');

        $this->log("Finding Template Task: " . $task_id);
        // find parent Task
        /** @var TemplateTask $task */
        $templateTask = $this->entityManager
            ->getRepository(TemplateTask::class)
            ->findOneBy(['id' => $task_id]);

        if( !$templateTask ) {
            $this->log('TemplateTask not found');
            throw new ResourceNotFoundException('TemplateTask not found');
        }

        $subtask = new TemplateSubtask();
        $subtask->setTask($templateTask);
        $subtask->setTitle($title);
        $subtask->setSubtaskType($subtask_type);

        if ($subtask_type === 1) {
            $subtask->setUrl($url);
        }

        $boxResponse = [];
        if ($uploadedFile !== null) {
            $prefix = "template-subtask/" . $task_id . "/";
            $result = $this->fileManager->processS3File($prefix, 'TemplateTask', $uploadedFile);
            $s3UploadResponse = $result["response"];
            $fileId = $s3UploadResponse["data"]["timestamp"];

            $subtask->setBoxId($fileId);
            $subtask->setFilesize($result["filesize"]);
            $subtask->setFilename($result["filename"]);
            $subtask->setMimeType($result["filetype"]);
            if (isset($result["pages"])) {
                $subtask->setPages(count($result["pages"]));
            }
        }

        if ($data['embedded_content']){
            $subtask->setEmbeddedContent($data['embedded_content']);
        }

        try {
            $em = $this->entityManager;
            $em->persist($subtask);
            $em->flush();
        } catch (Exception $e) {
            if ($uploadedFile !== null) {
                $this->log('*** Create Template Subtask Failed. Removing file from Box !');
                if($subtask_type == '0') {
                    $this->fileManager->deleteItem($prefix . $result["filename"]);
                }
                throw new InvalidResourceException(['templatesubtask' => ['Error while creating a Subtask: '.$e->getMessage()]]);
            }
            throw $e;
        }

        /** @var TemplateTask $templateTask */
        $templateSubtask = $this->entityManager
            ->getRepository(TemplateSubtask::class)
            ->findOneBy(['id' => $subtask->getId()]);

        if(!$templateSubtask) {
            $this->log('TemplateSubtask not found');
            throw new ResourceNotFoundException('TemplateSubtask not found');
        }

        $serializedData = $this->serializer->serialize(["template-subtask" => $templateSubtask], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Function to update Template Sub Tasks for admin
     *
     * @param Request       $request            Request Object
     * @param String        $templateSubtaskId     id of TemplateSubTask
     *
     * @throws InvalidResourceException
     * @throws Exception
     *
     * @return Response
     */
    public function updateTemplateSubtask(Request $request,$templateSubtaskId)
    {

        $this->log("Updating Template SUBTASK:".$request->get('title'));

        $this->validateRelationshipUpdate('template_task_id', $request);

        // Find the Subtask
        /** @var TemplateSubtask $subtask */
        $subtask = $this->entityManager
            ->getRepository(TemplateSubtask::class)
            ->findOneBy(['id' => $templateSubtaskId]);

        if(!$subtask) {
            $this->log('Subtask not found');
            throw new ResourceNotFoundException('Subtask not found');
        }

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
            $subtask->setPosition($request->get('position'));
        }

        $this->updateRecord(self::$ENTITY_NAME, $subtask);

        $serializedData = $this->serializer->serialize(["template-subtask" => $subtask], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Function to delete Template Sub Tasks for admin
     *
     * @param Request       $request            Request Object
     * @param String        $templateSubtaskId          id of the TemplateSubTask
     *
     * @throws Exception
     *
     * @return Response
     */
    public function deleteTemplateSubtask(Request $request, $templateSubtaskId)
    {

        $this->log('REMOVING SUBTASK: ' . $templateSubtaskId);

        // get Subtask
        /** @var TemplateSubtask $subtask */
        $subtask = $this->entityManager
            ->getRepository(TemplateSubtask::class)
            ->findOneBy(['id' => $templateSubtaskId]);

        $em = $this->entityManager;
        $em->remove($subtask);
        $em->flush();

        $responseObj = new Response();
        $responseObj->setStatusCode(204);

        return $responseObj;
    }
}
