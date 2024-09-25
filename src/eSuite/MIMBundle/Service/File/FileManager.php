<?php

namespace esuite\MIMBundle\Service\File;

use Aws\CloudFront\CloudFrontClient;
use Aws\S3\Exception\S3Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Entity\FileDocument;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\Subtask;
use esuite\MIMBundle\Entity\Task;
use esuite\MIMBundle\Entity\TemplateSubtask;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Exception\BoxGenericException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Service\Manager\Base as baseServiceManager;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\edotNotify;
use Psr\Log\LoggerInterface;
use Smalot\PdfParser\Parser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Exception;

class FileManager extends Base
{
    /**
     * @var array that contains the list of file extensions that can be uploaded as Session Content & in Subtasks
     */
    protected static $UPLOAD_FILE_TYPES = ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx', 'mp4', 'm4v', 'm4a', 'm4b', 'm4p', 'm4r'];

    public function fetchListItems($path)
    {
        $key = 'document-repository/' . $path;
        return $this->s3ObjectManager->getfroms3($key, true);
    }

    public function fetchS3ByPrefix($path){
        $key = 'document-repository/' . $path;
        return $this->s3ObjectManager->getFromS3WithPrefix($key, true);
    }

    public function storeItem($path, $file_path)
    {
        $key = 'document-repository/' . $path;
        return $this->s3ObjectManager->uploadToS3WithPath($key, $file_path, true);
    }

    public function copyExistingItem($sourceKey, $destination)
    {
        $destination = 'document-repository/' . $destination;
        $sourceKey = 'document-repository/' . $sourceKey;
        return $this->s3ObjectManager->copyExistingItemToS3($destination, $sourceKey, true);
    }

    public function checkItemExist($path)
    {
        $key = 'document-repository/' . $path;
        return $this->s3ObjectManager->checkIfResourceItemExists($key, true);
    }

    public function storeStreamItem($path, $content)
    {
        $key = 'document-repository/' . $path;
        return $this->s3ObjectManager->uploadToS3($key,$content, true);
    }

    public function deleteItem($path)
    {
        $key = 'document-repository/' . $path;
        $this->log("Deleting S3 file: $key");
        return $this->s3ObjectManager->removeFromS3($key, true);
    }

    public function getAWSPathKeyIfPossible($fileId){

        if (preg_match('/^S3/', (string) $fileId)) {
            $criteria = ['file_id' => $fileId];
        } else {
            $criteria = ['box_id' => $fileId];
        }

        $pathKey = false;
        /** @var FileDocument $fileDocument */
        $fileDocument = $this->em
            ->getRepository(FileDocument::class)
            ->findOneBy($criteria);

        if (!$fileDocument) {
            /** @var Subtask $subtaskDocument */
            $subtaskDocument = $this->em
                ->getRepository(Subtask::class)
                ->findOneBy($criteria);

            $this->log("Getting Subtask for ".print_r($criteria, true));
            if($subtaskDocument) {
                $this->log("Subtask found for ".print_r($criteria, true)." with Task folder ID: ".print_r($subtaskDocument->getTask()->getBoxFolderId(), true));
                $this->log("Subtask found for ".print_r($criteria, true)." with Task ID: ".$subtaskDocument->getTaskId());
                $pathKey = $this->createPathKey($subtaskDocument->getTaskId(), "T-".$subtaskDocument->getTaskId());
                $pathKey = $pathKey["key_path"] . "/" . $subtaskDocument->getFilename();
            } else {
                $this->log("Unable to find ".print_r($criteria, true));
            }
        } else {
            /** @var Session $sesion */
            $session = $fileDocument->getSession();

            $pathKey = $this->createPathKey($session->getBoxFolderId(),$session->getUid());
            $this->log("FileDocument Created Path key: ".print_r($pathKey, true));

            $pathKey = $pathKey["key_path"]."/".$fileDocument->getFilename();
        }

        return $pathKey;
    }

    /**
     * Function to return S3 URL Object if available
     * @param $fileId
     * @return array|string
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function isFromS3($fileId)
    {
        $response = '';
        $date = new \DateTime();

        /** @var FileDocument $fileDocument */
        $fileDocument = $this->em
            ->getRepository(FileDocument::class)
            ->findOneBy(['file_id' => $fileId]);

        $subtaskDocument = false;
        if (!$fileDocument) {
            /** @var Subtask $subtaskDocument */
            $subtaskDocument = $this->em
                ->getRepository(Subtask::class)
                ->findOneBy(['file_id' => $fileId]);
        }

        if (!$fileDocument && !$subtaskDocument) {
            $subtaskDocument = false;
            $this->log("File id: $fileId not found");
            $keyPath = base64_decode((string) $fileId);
            $charCount = substr_count($keyPath,"|");

            if ($charCount > 2) {
                $path = str_replace("|", "/", $keyPath);
                $response = [
                    "url" => $this->generateProgrammeDocumentUrl($path)
                ];
                return $response;
            }

            if (preg_match('/^S3/', (string) $fileId)) {
                $criteria = ['file_id' => $fileId];
            } else {
                $criteria = ['box_id' => $fileId];
            }

            /** @var FileDocument $fileDocument */
            $fileDocument = $this->em
                ->getRepository(FileDocument::class)
                ->findOneBy($criteria);

            if (!$fileDocument){
                /** @var Subtask $subtaskDocument */
                $subtaskDocument = $this->em
                    ->getRepository(Subtask::class)
                    ->findOneBy($criteria);
            }
        }

        if ($fileDocument) {
            $this->log("Found in FileDocument: $fileId");
            $status = $fileDocument->getUploadToS3();

            if ($status == "1") {
                $response = [
                    "url" => $this->generateProgrammeDocumentUrl($fileDocument->getAwsPath() . $fileDocument->getFilename())
                ];
            } else {
                /** @var Session $sesion */
                $session = $fileDocument->getSession();

                $pathKey = $this->createPathKey($session->getBoxFolderId(),$session->getUid());
                $this->log("FileDocument Created Path key: ".print_r($pathKey, true));

                $s3KeyPath = $pathKey["key_path"]."/".$fileDocument->getFilename();

                $this->log("FileDocument Full Path key: $s3KeyPath");
                $s3Object = $this->checkItemExist($s3KeyPath);

                if ($s3Object){
                    $fileDocument->setAwsPath($pathKey["key_path"] . "/");
                    $fileDocument->setUploadToS3(1);
                    $fileDocument->setFileId("S3" . $date->getTimestamp() . $this->getRandomString(4));
                    $this->em->persist($fileDocument);
                    $this->em->flush();

                    $generatedURL = $this->generateProgrammeDocumentUrl($s3KeyPath);
                    $this->log("FileDocument Generated URL: $generatedURL");
                    $response = [
                        "url" => $generatedURL
                    ];
                } else {
                    $this->log("Not yet uploaded to S3: $fileId");
                }
            }
        } else {
            if ($subtaskDocument) {
                $this->log("Found in Subtask: $fileId");
                $status = $subtaskDocument->getUploadToS3();

                if ($status == "1") {
                    $response = [
                        "url" => $this->generateProgrammeDocumentUrl($subtaskDocument->getAwsPath() . $subtaskDocument->getFilename())
                    ];
                } else {
                    $pathKey = $this->createPathKey($subtaskDocument->getTask()->getBoxFolderId(),$subtaskDocument->getTask()->getBoxFolderName());
                    $s3KeyPath = $pathKey["key_path"]."/".$subtaskDocument->getFilename();
                    $this->log("Task Created Path key: ".print_r($pathKey, true));
                    $this->log("Task Created Full Path key: $s3KeyPath");
                    $s3Object = $this->checkItemExist($s3KeyPath);

                    if ($s3Object){
                        $subtaskDocument->setAwsPath($pathKey["key_path"] . "/");
                        $subtaskDocument->setUploadToS3(1);
                        $subtaskDocument->setFileId("S3" . $date->getTimestamp() . $this->getRandomString(4));
                        $this->em->persist($subtaskDocument);
                        $this->em->flush();

                        $generatedURL = $this->generateProgrammeDocumentUrl($s3KeyPath);
                        $this->log("Subtask Generated URL: $generatedURL");
                        $response = [
                            "url" => $this->generateProgrammeDocumentUrl($s3KeyPath)
                        ];
                    } else {
                        $this->log("Not yet uploaded to S3 Subtask: $fileId");
                    }
                }
            } else {
                $this->log("Not Found in Subtask: $fileId");

                /** @var TemplateSubtask $templateSubtasks */
                $templateSubtasks = $this->em
                    ->getRepository(TemplateSubtask::class)
                    ->findOneBy(['box_id' => $fileId]);

                if ($templateSubtasks) {
                    $this->log("Found in TemplateSubtask: $fileId");

                    $s3KeyPath = "template-subtask/" . $templateSubtasks->getTaskId() . "/" . $templateSubtasks->getFilename();
                    $s3Object = $this->checkItemExist($s3KeyPath);

                    if ($s3Object){
                        $generatedURL = $this->generateProgrammeDocumentUrl($s3KeyPath);
                        $this->log("TemplateSubtask Generated URL: $generatedURL");
                        $response = [
                            "url" => $generatedURL
                        ];
                    }
                } else {
                    $this->log("Not Found in TemplateSubtask: $fileId");
                }
            }
        }

        return $response;
    }

    public function generateProgrammeDocumentUrl($path) {
        return $this->s3ObjectManager->generateSignedURLForDocumentRepo($path);
    }

    public function getItemDetails($path) {
        $key = 'document-repository/' . $path;
        return $this->s3ObjectManager->getFromS3($key, true);
    }

    /**
     * Creating a path
     *
     * @param $parentId
     * @param $parentName
     * @param $peopleSoftId
     *
     * @return array
     */
    public function createPathKey($parentId, $parentName, $peopleSoftId = false)
    {
        $this->log('Generating Pathkey for parent box id: ' . $parentId);
        $data = [];

        if (preg_match('/^T-/', (string) $parentName)) {
            $this->log('Checking task id');
            //save task doc to file docs
            /** @var Task $task */
            $task = $this->em
                ->getRepository(Task::class)
                ->findOneBy(['box_folder_id' => $parentId]);

            if (!$task){
                /** @var Task $task */
                $task = $this->em
                    ->getRepository(Task::class)
                    ->findOneBy(['id' => $parentId]);
            }

            if ($task) {
                $this->log('User task found');
                $prefix = "programme-documents/";
                $path = "prog-"
                    . $task->getCourse()->getProgrammeId()
                    . "/crs-"
                    . $task->getCourseId()
                    . "/stask-"
                    . $task->getId();
                $data["key_path"] = $prefix . $path;
                $data["name"] = "stask". $task->getId();
            } else {
                $this->log('User task not found');
            }

        } else {
            $this->log('Checking session id');

            $uid = preg_replace('/-S$/', '', $parentName);
            /** @var Session $userSession */
            $userSession = $this->em
                ->getRepository(Session::class)
                ->findOneBy(['uid' => $uid]);

            if ($peopleSoftId && $userSession) {
                $this->log('User session found');
                $prefix = "user-documents/";
                $path = $peopleSoftId . "/prog-"
                    . $userSession->getCourse()->getProgrammeId()
                    . "/crs-"
                    . $userSession->getCourseId()
                    . "/sess-"
                    . $userSession->getId();
                $data["key_path"] = $prefix . $path;
                $data["name"] = "sess". $userSession->getId();

            } else {
                $this->log('User session not found');

                /** @var Session $session */
                $session = $this->em
                    ->getRepository(Session::class)
                    ->findOneBy(['box_folder_id' => $parentId]);

                if ($session) {
                    $this->log('Session with box folder id found: ' . $parentId);
                    $prefix = "programme-documents/";
                    $path = "prog-"
                        . $session->getCourse()->getProgrammeId()
                        . "/crs-"
                        . $session->getCourseId()
                        . "/sess-"
                        . $session->getId();
                    $data["key_path"] = $prefix . $path;
                    $data["name"] = "sess". $session->getId();
                } else {
                    $this->log('Session with box folder id not found: ' . $parentId);
                }
            }
        }

        return $data;
    }

    /** Function to randomly generate a string
     *
     * @param int $length of the random String
     *
     * @return String
     */
    public function getRandomString($length = 8)
    {
        return $this->s3ObjectManager->generateRandomString($length);
    }

    /**
     * Upload the file to S3
     *
     * @param $uid
     * @param $sourcePath
     * @param $filename
     *
     * @return string
     * @throws ResourceNotFoundException
     */
    public function uploadUserDocumentsToS3(Request $request, $uid, $sourcePath, $filename)
    {
        $this->log('Creating a copy to S3 using uid: '.$uid);
        $response = null;

        $isSubtaskDocumentAnnotations = false;
        $criteria = null;

        if (is_array($uid)){
            $uid = $uid[0];
        }
        $uid = explode("|", base64_decode((string) $uid));//4

        $suffix = $uid[count($uid) - 1];
        if (strtolower($suffix) === strtolower("SubtaskDocumentAnnotations")) {
            $isSubtaskDocumentAnnotations = true;
        } else {
            $sessionId = str_replace("sess-", "", $uid[4]);
            $criteria = ['id' => $sessionId];
        }

        /** @var User $user */
        $user = $this->baseServiceManager->getCurrentUser($request);
        if($isSubtaskDocumentAnnotations){
            $prefix = "user-documents/" . $user->getPeoplesoftId() . "/SubtaskDocumentAnnotations/";
            $key = $prefix . $filename;
            $this->log('Copying key to S3:' . $key);
            $response = $this->storeItem($key, $sourcePath);
            if ($response){
                return $key;
            } else {
                $this->log("Upload to S3 Failed");
            }
        }

        if (!empty($uid)) {
            //Save to event folder
            if ($user) {
                /** @var Session $session */
                $session = $this->em
                    ->getRepository(Session::class)
                    ->findOneBy($criteria);

                if ($session && $user) {
                    /** @var User $user */
                    $user = $this->baseServiceManager->getCurrentUser($request);

                    $prefix = "user-documents/" . $user->getPeoplesoftId() . "/prog-"
                        . $session->getCourse()->getProgrammeId()
                        . "/crs-"
                        . $session->getCourseId()
                        . "/sess-"
                        . $session->getId()
                        . "/";

                    $key = $prefix . $filename;
                    $this->log('Copying key to S3:' . $key);
                    $response = $this->storeItem($key, $sourcePath);
                    if ($response){
                        return $key;
                    } else {
                        $this->log("Upload to S3 Failed");
                    }
                } else {
                    $this->log("Using UID: ".print_r($uid,true));
                    $this->log('SESSION and or User Account NOT User ID: '.$user->getId().' using criteria: '.print_r($criteria, true));
                }
            } else {
                $this->log('UserID NOT FOUND');
            }
        } else {
            $this->log('UID NOT FOUND');
        }

        return $response;
    }

    public function processS3File($prefix, $parentType, UploadedFile $uploadedFile)
    {
        $this->log('Process S3 File');
        $pages = 0;

        try {
            // get file info
            $filename = $uploadedFile->getClientOriginalName();
            $fileType = $uploadedFile->getMimeType();
            $filesize =  $uploadedFile->getSize();

            $uploadedFile->move($this->getDocumentUploadDir(), $filename);

            $path = $this->getDocumentUploadDir().$filename;

            $key = $prefix . $filename;

            // Get File extension
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            // Check if it is one of allowed file extension types
            if (!in_array($ext, self::$UPLOAD_FILE_TYPES)) {
                throw new InvalidResourceException(['file_document' => ['Please select a valid file type. Allowed types are Word, Excel, PPT, PDF & MPEG-4.']]);
            }

            // if PDF file, store no. of pages
            if ($fileType === 'application/pdf') {
                $parser = new Parser();

                try {
                    $pdf = $parser->parseFile($path);
                    $pages = count($pdf->getPages());
                } catch (Exception) {
                }
            }

            // upload file to S3
            $s3Response = $this->storeItem($key, $path);

        } catch (BoxGenericException $e) {
            $this->log('Error while uploading a file in S3 for a ' . $parentType . ': ' . $e->getMessage());
            throw $e;
        }

        $data = [
            "filename"  => $filename,
            "filesize"  => $filesize,
            "filetype"  => $fileType,
            "page"      => $pages,
            "path"      => $path,
            "response"  => $s3Response
        ];

        return $data;
    }

}
