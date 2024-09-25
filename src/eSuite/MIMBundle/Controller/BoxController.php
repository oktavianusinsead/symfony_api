<?php

namespace esuite\MIMBundle\Controller;

use Aws\Api\DateTimeResult;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\Subtask;
use esuite\MIMBundle\Entity\Task;
use esuite\MIMBundle\Entity\TemplateSubtask;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Exception\GenericException;
use esuite\MIMBundle\Exception\BoxGenericException;
use esuite\MIMBundle\Entity\FileDocument;

use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Service\File\FileManager;
use Exception;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\edotNotify;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use esuite\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Documents")]
class BoxController extends BaseController
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
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $s3Object = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $this->fileManager = new FileManager($baseParameterBag->get('edot.s3.config'), $logger, $notify, $em, $s3Object, $base);
    }

    #[Post("/box/folders")]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a new folder on Box")]
    public function createFolderAction(Request $request)
    {
        $this->setLogUuid($request);

        $parentId = $request->get('parent')['id'];
        $name = $request->get('name');

        /** @var User $user */
        $user = $this->getCurrentUser($request);

        if ($name === 'SubtaskDocumentAnnotations'){
            $boxResponse = [
                "type" => "folder",
                "id" => base64_encode("user-documents/" . $user->getPeoplesoftId() . "/SubtaskDocumentAnnotations"),
                "name" => "SubtaskDocumentAnnotations",
                "size" => 200,
                "item_status" => "active",
                "item_collection" => [
                    "entries" => []
                ]
            ];

        } else {
            $sessionUID = $name;

            /** @var Session $session */
            $session = $this->doctrine
                ->getRepository(Session::class)
                ->findOneBy(['uid' => $sessionUID]);

            if (!$session) {
                // try removing -S
                $uid = preg_replace('/-S$/', '', $sessionUID);
                /** @var Session $session */
                $session = $this->doctrine
                    ->getRepository(Session::class)
                    ->findOneBy(['uid' => $uid]);
            }

            if ($session) {

                $folderName = $session->getUid();
                if (str_ends_with((string) $sessionUID, "-S")) {
                    $folderName .= "-S";
                }

                $prefix = "user-documents|" . $user->getPeoplesoftId() . "|prog-"
                    . $session->getCourse()->getProgrammeId()
                    . "|crs-"
                    . $session->getCourseId()
                    . "|sess-"
                    . $session->getId();

                $this->log("Virtual folder: $prefix");

                $boxResponse = [
                    "type" => "folder",
                    "id" => base64_encode($prefix),
                    "name" => $folderName,
                    "size" => 200,
                    "item_status" => "active",
                    "item_collection" => [
                        "entries" => []
                    ]
                ];
            } else {
                $this->log("Session not exists: $sessionUID with parent: $parentId");
                throw new BoxGenericException("Session not exists: $sessionUID with parent: $parentId");
            }
        }

        return new Response( json_encode( $boxResponse ), 201 );
    }

    #[Get("/box/folders/{id}/items")]
    #[OA\Response(
        response: 200,
        description: "Handler function to Get folder items from Box")]
    public function getFolderItemsAction(Request $request)
    {
        $this->setLogUuid($request);

        $id           = $request->get('id');
        $boxResponse  = "";

        /** @var User $user */
        $user = $this->getCurrentUser($request);

        if ($id == "0") {
            //Get event docs list by user programmes
            $boxEntries = [];

            $boxResponse = [
                "type" => "folder",
                "id"   => "0",
                "name" => "All Files",
                "size" => 200,
                "item_status" => "active",
                "item_collection" => [
                    "entries" => []
                ]
            ];

            $userId = $user->getId();
            $peopleSoftId = $user->getPeoplesoftId();

            /** @var Query $query */
            $query = $this->doctrine->getManager()->createQuery(
                'SELECT p FROM esuite\MIMBundle\Entity\Programme p
                            JOIN p.courses c
                            JOIN c.courseSubscriptions cs
                            JOIN cs.user u
                            WHERE c.published = :published and p.published = :published and u.id = :user_id'
            )
            ->setParameter('published', TRUE)
            ->setParameter('user_id', $userId);

            $programmesList = $query->getResult();
            // Serialize only published sub-entities
            foreach($programmesList as $programme) {
                /** @var Programme $programme */
                $programme->serializeOnlyPublished(TRUE);
                $programme->setHideCourses(TRUE);
                $programme->setForParticipant(true);
                $programme->setIncludeHidden(true);
                $programme->setRequestorId($userId);

                if (!$programme->getArchived()) {
                    /** @var Course $course */
                    foreach ($programme->getCourses() as $course){
                        if ($course->getPublished()){
                            /** @var Session $session */
                            foreach($course->getSessions() as $session){
                                if ($session->getPublished()){
                                    $prefix = "user-documents|";
                                    $path = $peopleSoftId . "|prog-"
                                        . $session->getCourse()->getProgrammeId()
                                        . "|crs-"
                                        . $session->getCourseId()
                                        . "|sess-"
                                        . $session->getId();
                                    $fileID = $prefix . $path;

                                    $sessionFolderName = $session->getUid();
                                    if (!in_array($sessionFolderName, array_column($boxEntries, "name"))) {
                                        $boxEntries[] = [
                                            "id" => base64_encode($fileID),
                                            "type" => "folder",
                                            "name" => $sessionFolderName
                                        ];
                                    }

                                    $sessionFolderName.="-S";
                                    if (!in_array($sessionFolderName, array_column($boxEntries, "name"))) {
                                        $boxEntries[] = [
                                            "id" => base64_encode($fileID . "|-S"),
                                            "type" => "folder",
                                            "name" => $sessionFolderName
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $path = "user-documents|$peopleSoftId|SubtaskDocumentAnnotations";
            $boxEntries[] = [
                "id" => base64_encode($path),
                "type" => "folder",
                "name" => "SubtaskDocumentAnnotations"
            ];

            $boxResponse["item_collection"]["entries"] = $boxEntries;

        } else {
            //use s3 file path
            $id = base64_decode((string) $id);
            $keyPath = str_replace("|", "/", $id);


            if ($keyPath != "") {
                $boxResponse = $this->getUserContents($request, $keyPath, false);
            }
        }

        return $boxResponse;
    }

    #[Get("/box/files/{id}")]
    #[OA\Response(
        response: 200,
        description: "Handler function to Get metadata for a file on Box")]
    public function getFileMetadataAction(Request $request)
    {
        $this->setLogUuid($request);

        $fileId = $request->get( 'id' );

        $s3manager = $this->fileManager;
        $linkPath = $s3manager->isFromS3($fileId);
        if (!empty($linkPath["url"])) {
            $testForKeyPath = $s3manager->getAWSPathKeyIfPossible($fileId);
            if ($testForKeyPath){
                $fileId = $testForKeyPath;
            } else {
                $fileId = base64_decode((string) $fileId);
            }
        } else {
            $fileId = base64_decode((string) $fileId);
        }

        $keyPath = str_replace("|", "/", $fileId);
        /** @var array $s3Response */
        $s3Response = $s3manager->getItemDetails($keyPath);
        if ($s3Response) {
            /** @var DateTimeResult $awsLastodifeid */
            $awsLastModifeid = $s3Response['LastModified'];

            $prefixArray = explode("/",$keyPath);
            $fileName = $prefixArray[count($prefixArray) - 1];

            $prefixArrayWithoutFilename = $prefixArray;
            array_pop($prefixArrayWithoutFilename);

            $sessionS3 = $prefixArrayWithoutFilename[4];
            $sessionArray = explode("-",$sessionS3);
            $sessionId = $sessionArray[1];
            $parentName = "";

            /** @var Session $session */
            $session = $this->doctrine
                ->getRepository(Session::class)
                ->findOneBy(['id' => $sessionId]);

            if ($session) {
                $parentName = $session->getUid();
                if (str_contains($fileName, 'annotation.jso')) {
                    if (strlen($parentName) > 0) {
                        $parentName .= "-S";
                    }
                }
            }

            $boxResponse = $this->formatUploadResponse(
                base64_encode(str_replace("/", "|", $fileId)),
                sha1((string)$s3Response['Body']),
                $fileName,
                $s3Response['ContentLength'],
                (new \DateTime())->setTimestamp($awsLastModifeid->getTimestamp()),
                base64_encode(implode("|",$prefixArrayWithoutFilename)),
                $parentName
            );
        } else {
            throw new BoxGenericException("Unable to fetch Metadata for $fileId");
        }

        return $boxResponse;
    }

    #[Delete("/box/files/{id}")]
    #[OA\Response(
        response: 200,
        description: "Handler function to DELETE a file on Box")]
    public function deleteFileAction(Request $request)
    {
        $this->setLogUuid($request);
        $fileId = $request->get( 'id' );
        $fileId = base64_decode((string) $fileId);
        $pathArray = explode("|", $fileId);
        array_splice($pathArray, 0, 1);
        $keyPath = "user-documents/".implode("/", $pathArray);
        $this->fileManager->deleteItem($keyPath);

        return new Response( null, 204 );
    }

    #[Get("/box/files/{id}/content")]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve URL where a file can be accessed for a limited time")]
    public function getFileUrlAction(Request $request)
    {
        $this->setLogUuid($request);
        $fileId = $request->get( 'id' );

        /** @var FileManager $s3manager */
        $s3manager = $this->fileManager;
        $linkPath = $s3manager->isFromS3($fileId);
        $link = "";

        if (!empty($linkPath["url"])) {
            $link = $linkPath["url"];
        }

        // send url
        return new Response(json_encode(['url' => $link], JSON_UNESCAPED_SLASHES));
    }

    #[Post("/box/files/{id}/content")]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a file on Box")]
    public function updateFileAction(Request $request)
    {
        $this->setLogUuid($request);

        // decode attachment metadata
        $data = json_decode( (string) $request->get( 'data' ), true );

        if(!isset($data["parent"])) {
            $this->log("Exception while updating a S3 file: parent folder was not given");
            throw new GenericException( 500, 'S3 Error: 500' );
        }

        if(is_null($request->files->get('file'))) {
            $this->log("Exception while updating a S3 file: file was not given");
            throw new GenericException( 500, 'S3 Error: 500' );
        }

        $folderId = $data["parent"]["id"];

        // get file
        $uploadedFile = $request->files->get('file');
        $filename = $uploadedFile->getClientOriginalName();
        $uploadedFile->move($this->getDocumentUploadDir(), $filename);
        $sourcePath = $this->getDocumentUploadDir().$filename;

        /** @var FileManager $s3manager */
        $s3manager = $this->fileManager;

        // store item
        $sessionUid = $folderId;
        $key = $this->fileManager->uploadUserDocumentsToS3($request, $sessionUid, $sourcePath, $filename);
        $s3Response = $s3manager->getItemDetails($key);
        if ($s3Response) {
            return $this->getUserContents($request, $key);
        } else {
            throw new BoxGenericException("Unable to fetch Metadata for $key");
        }
    }

    #[Post("/box/files")]
    #[OA\Response(
        response: 200,
        description: "Handler function to Upload a file to Box")]
    public function uploadFileAction(Request $request)
    {
        $this->setLogUuid($request);

        // decode attachment metadata
        $data = json_decode((string) $request->get('data'), true);

        if(!isset($data["parent"])) {
            $this->log("Exception while uploading a S3 file: parent folder was not given");
            throw new GenericException( 500, 'S3 Error: 500' );
        }

        if(is_null($request->files->get('file'))) {
            $this->log("Exception while updating a S3 file: file was not given");
            throw new GenericException( 500, 'S3 Error: 500' );
        }

        $folderId = $data["parent"];

        // get file
        $uploadedFile = $request->files->get('file');
        $filename = $uploadedFile->getClientOriginalName();
        $uploadedFile->move($this->getDocumentUploadDir(), $filename);
        $sourcePath = $this->getDocumentUploadDir() . $filename;

        /** @var FileManager $s3manager */
        $s3manager = $this->fileManager;

        // store item
        $sessionUid = $folderId['id'];
        $key = $this->fileManager->uploadUserDocumentsToS3($request, $sessionUid, $sourcePath, $filename);
        $s3Response = $s3manager->getItemDetails($key);
        if ($s3Response && $key) {
            return $this->getUserContents($request, $key, true);
        } else {
            throw new BoxGenericException("Unable to fetch Metadata for $key");
        }
    }

    #[OA\Parameter(name: "isFileNameIncluded", description: "IsfFile name included", in: "query", schema: new OA\Schema(type: "bool"))]
    #[OA\Parameter(name: "keyPath", description: "Key Path", in: "query", schema: new OA\Schema(type: "string"))]
    private function getUserContents(Request $request, $keyPath, $isFileNameIncluded = true){
        /** @var User $user */
        $user = $this->getCurrentUser($request);

        /** @var FileManager $s3manager */
        $s3manager        = $this->fileManager;

        $prefixArray      = explode("/",(string) $keyPath);
        $parentFolderName = "All Files";

        $isJSONAnnotationFolder = false;
        $session = false;
        //Check if SubtaskDocumentAnnotation
        if (count($prefixArray) > 0) {
            $suffix = $prefixArray[count($prefixArray) - 1];
            if (strtolower($suffix) === strtolower("SubtaskDocumentAnnotations")) {
                $parentFolderName = "SubtaskDocumentAnnotations";
            } else {
                if (count($prefixArray) > 4) {
                    if (array_key_exists(5, $prefixArray)) {
                        $checkJSONAnnotation = $prefixArray[5];
                        if ($checkJSONAnnotation === '-S') {
                            $isJSONAnnotationFolder = true;
                            array_splice($prefixArray, 5, 1);
                        }
                    }
                }

                $uncleanedSession = $prefixArray[4];
                $s3SessionName = explode("-", $uncleanedSession);
                $session = null;
                if (count($s3SessionName) == 2) {
                    $sessionId = $s3SessionName[1];

                    /** @var Session $session */
                    $session = $this->doctrine
                        ->getRepository(Session::class)
                        ->findOneBy(['id' => $sessionId]);

                    if ($session) {
                        $parentFolderName = $session->getUid();
                    }
                }

                if ($isJSONAnnotationFolder) {
                    $parentFolderName .= "-S";
                }

                if ($isFileNameIncluded) {
                    array_pop($prefixArray); // remove filename
                }
            }
        }

        $preferredID = implode("/",$prefixArray);
        $preferredFolderID = implode("|",$prefixArray);

        $linkPath = $s3manager->fetchS3ByPrefix($preferredID);
        $s3Array = [];

        if (!empty($linkPath['Contents'])) {
            foreach ($linkPath['Contents'] as $object) {
                $pathArray = explode("/", (string) $object['Key']);
                $filename = end($pathArray);

                if ($filename != "") {
                    array_splice($pathArray, 0, 2);
                    if ($isJSONAnnotationFolder){
                        if (str_contains($filename, 'annotation.jso')){
                            $s3Array[$filename] = $this->formatFileObject(implode("|", $pathArray),$filename,$object['Size'],$object['LastModified']);
                        }
                    } else {
                        if (!str_contains($filename, 'annotation.jso') || $parentFolderName === "SubtaskDocumentAnnotations") {
                            $s3Array[$filename] = $this->formatFileObject(implode("|", $pathArray),$filename,$object['Size'],$object['LastModified']);
                        }
                    }
                }
            }
        }

        if (count($s3Array) == 0) {
            if ($session) {
                $preferredFolderID = "user-documents|" . $user->getPeoplesoftId() . "|prog-"
                    . $session->getCourse()->getProgrammeId()
                    . "|crs-"
                    . $session->getCourseId()
                    . "|sess-"
                    . ($isJSONAnnotationFolder ? $session->getId() . "|-S" : $session->getId());
            } else {
                $preferredFolderID = "user-documents|" . $user->getPeoplesoftId() . "|SubtaskDocumentAnnotations";
            }
        }

        return [
            "type" => "folder",
            "id" => base64_encode($preferredFolderID),
            "name" => $parentFolderName,
            "size" => 200,
            "item_status" => "active",
            "item_collection" => [
                "entries" => array_values($s3Array)
            ]
        ];
    }

    private function formatFileObject($path,$filename, $size, $lastModified ){
        return [
            "type" => "file",
            "id"   => base64_encode((string) $path),
            "name" => $filename,
            "size" => $size,
            "sha1" => null,
            "modified_at" => $lastModified
        ];
    }

    private function formatUploadResponse($s3Key, $sha1, $filename, $size, $modified_at, $parentId, $parentName){
        return ["type"        => "file", "id"          => $s3Key, "etag"        => "0", "sha1"        => $sha1, "name"        => $filename, "size"        => $size, "modified_at" => $modified_at, "parent"      => [
            "type" => "folder",
            "id"   => $parentId,
            "name" => $parentName
        ]];
    }

}
