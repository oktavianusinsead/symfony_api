<?php

namespace Insead\MIMBundle\Controller;

use Exception;
use \DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Service\File\FileManager;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Session;
use Insead\MIMBundle\Entity\FileDocument;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\StudyCourseBackup;
use Insead\MIMBundle\Service\StudyNotify;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Documents")]
class FileDocumentController extends BaseController
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
    public static $ENTITY_NAME = "FileDocument";

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $EMBER_NAME = "file_document";

    #[Get("/file-documents/{id}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get file")]
    public function getFileDocumentAction( Request $request )
    {
        $this->setLogUuid($request);

        // get document that matches id, findById will return 404 response if not found
        $fileDoc = $this->findById(self::$ENTITY_NAME, $request->get( 'id' ) );

        return [self::$EMBER_NAME => $fileDoc];
    }

    #[Get("/file-documents")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple FileDocuments")]
    public function getFileDocumentsAction(Request $request)
    {
        $this->setLogUuid($request);

        $fileDocs = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log(self::$ENTITY_NAME.": ".$id);
            array_push($fileDocs, $this->findById(self::$ENTITY_NAME, $id));
        }
        return ['file_documents' => $fileDocs];
    }

    #[Post("/file-documents")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to upload a new Document. This API endpoint is restricted to coordinators only")]
    public function uploadFileDocumentAction(Request $request, StudyNotify $studyNotify, StudyCourseBackup $studyCourseBackup)
    {
        $this->setLogUuid($request);

        /** @var FileManager $s3manager */
        $s3manager = $this->fileManager;

        //Check Header 'Content-Type'
        if(!(preg_match('/multipart\/form-data/', (string) $request->headers->get('Content-Type')))) {
            throw new InvalidResourceException(['file_document' => ['Please select a valid file to upload.']]);
        }

        // decode attachment metadata
        $data = json_decode((string) $request->get('data'), true);

        // get file
        $uploadedFile = $request->files->get('file');

        // get parent session
        /** @var Session $session */
        $session = $this->findById('Session', $this->getPropertyFromObj($data, 'session_id'));

        $this->checkReadWriteAccess($request,$session->getCourseId());

        $prefix = "programme-documents/prog-"
            . $session->getCourse()->getProgrammeId()
            . "/crs-"
            . $session->getCourseId()
            . "/sess-"
            . $session->getId()
            . "/";

        if ($s3manager->checkItemExist($prefix . $uploadedFile->getClientOriginalName())) {
            throw new InvalidResourceException(['file_document' => ['File already exists.']]);
        }

        $result = $s3manager->processS3File($prefix, 'Session', $uploadedFile);
        $s3UploadResponse = $result["response"];
        $fileId = $s3UploadResponse["data"]["timestamp"];

        // create new model instance
        $fileDoc = new FileDocument();

        try {
            // populate model
            $fileDoc->setSession($session);
            $fileDoc->setBoxId($fileId);
            $fileDoc->setTitle($this->getPropertyFromObj($data, 'title'));
            $fileDoc->setDescription($this->getPropertyFromObj($data, 'description'));
            $fileDoc->setPath($result["path"]);
            $fileDoc->setMimeType($result["filetype"]);
            $fileDoc->setDocumentType($this->getPropertyFromObj($data, 'document_type'));
            $fileDoc->setContent($this->getPropertyFromObj($data, 'content'));
            $fileDoc->setPosition($this->getPropertyFromObj($data, 'position'));
            $fileDoc->setUploadToS3(1);
            $fileDoc->setAwsPath($prefix);
            $fileDoc->setFileId($s3UploadResponse["data"]["timestamp"]);

            if($this->getPropertyFromObj($data, 'due_date')) {
                $fileDoc->setDueDate(new DateTime($this->getPropertyFromObj($data, 'due_date')));
            }
            $fileDoc->setFilename($result["filename"]);
            $fileDoc->setDuration($this->getPropertyFromObj($data, 'duration'));

            if($this->getPropertyFromObj($data, 'publish_at')) {
                $fileDoc->setPublishAt(new DateTime($this->getPropertyFromObj($data, 'publish_at')));
            }
            $fileDoc->setFilesize($result["filesize"]);

            if (isset($result["pages"])) {
                $fileDoc->setPages(count($result["pages"]));
            }

        } catch(Exception $e) {
            $this->log('*** Upload Document Failed. Removing file from S3 !');
            $this->fileManager->deleteItem($prefix . $result["filename"]);

            throw $e;
        }

        try {
            $responseObj = $this->create(self::$EMBER_NAME, $fileDoc);
        } catch (Exception $e) {
            $this->log('VALIDATION FAILED, SO REMOVING FILE FROM S3');
            $this->fileManager->deleteItem($prefix . $result["filename"]);

            throw $e;
        }

        // Send Push notification and update course backup if session is published and publish_at timestamp is before current time
        if($session->getPublished() && $fileDoc->getPublishAt() < (new DateTime())) {
            try {
                // Update Course Backup
                $backupService = $studyCourseBackup;
                $backupService->updateCoursebackup($session->getCourse());

                //Push notifications
                $notify = $studyNotify;
                $notify->setLogUuid($request);
                $notify->message($session->getCourse(), self::$ENTITY_NAME);
            } catch (Exception $e) {
                $this->log('Course Backup update failed, removing file from S3');
                $this->fileManager->deleteItem($prefix . $result["filename"]);

                throw $e;
            }
        }

        return $responseObj;
    }

    private function getPropertyFromObj($object, $property)
    {
        if($object !== null) {
            if(array_key_exists($property, $object)) {
                $this->log('Property Found:'.$property);
                return $object[$property];
            }
        }

        return "";
    }

    #[Post("/file-documents/{id}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update FileDocument")]
    public function updateFileDocumentAction( Request $request, StudyNotify $studyNotify, StudyCourseBackup $studyCourseBackup)
    {
        $this->setLogUuid($request);

        // find model
        /** @var FileDocument $fileDoc */
        $fileDoc = $this->findById( self::$ENTITY_NAME, $request->get( 'id' ) );

        $course = $fileDoc->getSession()->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        $this->validateRelationshipUpdate('session_id', $request);

        // update
        if ($request->get('title')) {
            $fileDoc->setTitle($request->get('title'));
        }
        if ($request->get('description')) {
            $fileDoc->setDescription($request->get('description'));
        }
        if ( !is_null($request->get('document_type')) ) {
            $fileDoc->setDocumentType($request->get('document_type'));
        }

        if($request->request->has('position')) {
            $fileDoc->setPosition($request->get('position'));
        }

        if ($request->get('due_date')) {
            $fileDoc->setDueDate(new DateTime($request->get('due_date')));
        }
        if ( !is_null($request->get('duration')) ) {
            $fileDoc->setDuration($request->get('duration') || 0);
        }

        if ($request->get('publish_at')) {

            if ((new DateTime($request->get('publish_at'))) > new DateTime()) {

                // marking document on-command and set at specific datetime
                $fileDoc->setPublishAt(new DateTime($request->get('publish_at')));

            } elseif ((new DateTime($request->get('publish_at'))) < new DateTime()) {

                // publish immediately and publish handout
                $currentDateTime = new DateTime();
                $currentDateTime->modify("-5 minutes");
                $fileDoc->setPublishAt($currentDateTime);

                //Push notifications
                $notify = $studyNotify;
                $notify->setLogUuid($request);
                $notify->message($fileDoc->getSession()->getCourse(), self::$ENTITY_NAME);

                // Update Course Backup
                $this->log("Needs a Backup reset.");
                $backupService = $studyCourseBackup;
                $backupService->updateCoursebackup($course);
            }
        }

        // save and render response
        $response = $this->update(self::$EMBER_NAME, $fileDoc);

        return $response;
    }

    #[Delete("/file-documents/{id}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "request ", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "id ", description: "id of file document to be deleted", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update FileDocument")]
    public function deleteFileDocumentAction(Request $request, $id, StudyNotify $studyNotify, StudyCourseBackup $studyCourseBackup)
    {
        $this->setLogUuid($request);

        // get document that matches id - method with throw 404 if not found
        /** @var FileDocument $fileDocument */
        $fileDocument = $this->findById(self::$ENTITY_NAME, $id);

        $course = $fileDocument->getSession()->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        /** @var Session $session */
        $session = $fileDocument->getSession();
        $publishTime = $fileDocument->getPublishAt();

        // Remove file from S3 first
        try {

            // remove from s3
            if ($fileDocument->getUploadToS3() == '1') {
                $prefix = "programme-documents/prog-"
                    . $session->getCourse()->getProgrammeId()
                    . "/crs-"
                    . $session->getCourseId()
                    . "/sess-"
                    . $session->getId()
                    . "/";

                $path = $prefix . $fileDocument->getFilename();
                $this->fileManager->deleteItem($path);
            }

        } catch (Exception) {
            $this->log('ERROR:: Failed to remove file with id: ' . $id . ' from S3.');
        }

        // If successfully removed file from S3, delete from database
        $responseObj = $this->deleteById(self::$ENTITY_NAME, $id);

        if(($publishTime < new DateTime()) && $session->getPublished()) {
            //Push notifications
            $notify = $studyNotify;
            $notify->setLogUuid($request);
            $notify->message($session->getCourse(), self::$ENTITY_NAME);

            // Update Course Backup
            $this->log("Needs a Backup reset.");
            $backupService = $studyCourseBackup;
            $backupService->updateCoursebackup($course);
        }

        //update session updated at
        $session->setUpdatedValue();
        $em = $this->doctrine->getManager();
        $em->persist($session);
        $em->flush();

        return $responseObj;
    }
}
