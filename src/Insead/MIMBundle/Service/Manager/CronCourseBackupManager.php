<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Insead\MIMBundle\Entity\GroupSessionAttachment;
use Insead\MIMBundle\Entity\Link;
use Insead\MIMBundle\Entity\LinkedDocument;
use Insead\MIMBundle\Entity\Session;
use Insead\MIMBundle\Service\File\FileManager;
use Insead\MIMBundle\Service\S3ObjectManager;

use Exception;
use ZipArchive;
use DateTime;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

use Doctrine\Common\Collections\Criteria;

use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\FileDocument;
use Insead\MIMBundle\Entity\CourseBackup;
use Insead\MIMBundle\Entity\Subtask;
use Insead\MIMBundle\Entity\Task;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeExtensionGuesser;


class CronCourseBackupManager extends Base
{
    protected $rootDir;
    protected $uploadDir;
    protected $s3;
    protected $fileManager;

    public function loadServiceManager(S3ObjectManager $s3, $config, FileManager $fileManager)
    {
        $this->s3                   = $s3;
        $this->rootDir = $config["kernel_root"];
        $this->uploadDir = $config["upload_temp_folder"];
        $this->fileManager = $fileManager;
    }

    /**
     * Function to process the course backups
     *
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function processCourseBackup()
    {
        $now = new \DateTime();

        //wait for 1 HR before redoing backups
        $interval = new \DateInterval('PT1H');
        $bufferTime = new \DateTime();
        $bufferTime->sub($interval);

        //Find Course Backup which have in_progress == true
        $criteria = new Criteria();
        $expr = $criteria->expr();

        $criteria->where( $expr->eq('in_progress',true) );
        $criteria->andWhere(
            $expr->orX(
                $expr->lt('start_at',$bufferTime),
                $expr->eq('start_at',null)
            )
        );

        $criteria->orderBy(
            ["completed" => $criteria::ASC]
        );

        $courseBackups = $this->entityManager
            ->getRepository(CourseBackup::class)
            ->matching($criteria);

        $this->log('Backups to process::' . count($courseBackups));

        //check if there are old backups to process
        if( !count($courseBackups) ) {
            $criteria = new Criteria();
            $criteria->where( $expr->eq('in_progress',false) );
            $criteria->andWhere(
                $expr->orX(
                    $expr->lt('start_at',$bufferTime),
                    $expr->eq('start_at',null)
                )
            );
            $criteria->andWhere(
                $expr->orX(
                    $expr->contains('s3_path','course\_'),
                    $expr->contains('s3_path','-%20course')
                )
            );

            $criteria->orderBy(
                ["completed" => $criteria::ASC]
            );

            $courseBackups = $this->entityManager
                ->getRepository(CourseBackup::class)
                ->matching($criteria);

            $this->log('Old Backups to reprocess::' . count($courseBackups));
        }

        //there are no backups to process
        if( !count($courseBackups) ) {
            return ["msg" => "No Backup requests to process!"];

        }

        //backups to process
        /** @var CourseBackup $courseBackup */
        $courseBackup = $courseBackups[0];

        $course = $courseBackup->getCourse();

        //if the updated_at field is recently changed (about an hour ago), do not include item in the process
        if( !is_null($courseBackup->getStart()) && $courseBackup->getStart() >= $bufferTime ) {
            $this->log('Skipping Course::' . $course->getId() . ' - last started ' . $courseBackup->getStart()->format('Y-m-d H:i:s') );

        } else {
            if( $courseBackup->getStart() ) {
                $this->log('Course::' . $course->getId() . ' - last start ' . $courseBackup->getStart()->format('Y-m-d H:i:s') );
            } else {
                $this->log('Course::' . $course->getId() . ' - no start date yet' );
            }

            //set new start_at field to serve as a flag that the process has started
            $courseBackup->setStart($now);
            $em = $this->entityManager;
            $em->persist($courseBackup);
            $em->flush();

            $this->processBackupForCourse( $course, $courseBackup );
        }

        // Return Success
        return ["msg" => "Processed 1 out of " . count($courseBackups) . " requests", "processed" => $courseBackup];

    }

    /**
     * Function to obtain the file extension of a given URl of a file
     *
     * @param Course            $course             Object of the Course that needs to be processed
     * @param CourseBackup      $courseBackup       Object of the CourseBackup where the Course came from
     */
    protected function processBackupForCourse( Course $course, CourseBackup $courseBackup ) {
        $now = new \DateTime();

        //Temporary location for the files
        $uploadDir = $this->getDocumentUploadDir();

        $em = $this->entityManager;

        try {
            //tasks
            $allTasks = $this->prepareTaskData( $course );

            //sessions
            $allSessions = $this->prepareSessionData( $course, $now );

            $allDocs = ["sessions" => $allSessions, "tasks" => $allTasks];

            $this->prepareFolders( $uploadDir, $course, $allDocs );

            $this->prepareProfileBook( $uploadDir, $course );

            $this->prepareLearningJourney( $uploadDir, $course );

            $this->prepareProgrammeCalendar( $uploadDir, $course );

            $this->prepareSessionSheet( $uploadDir, $course );

            if( isset($allDocs["tasks"]) ) {
                $this->downloadSubtasks( $allDocs["tasks"], $uploadDir, $course );
            }

            if( isset($allDocs["sessions"]) ) {
                $this->downloadSessionDocs( $allDocs["sessions"], $uploadDir, $course );
            }

            if( !$this->isDirEmpty($uploadDir.$course->getUid()) ) {
                // Zip the Course folder
                $fileSize = $this->zipFolder($uploadDir . $course->getUid(), $uploadDir . $course->getUid() . '.zip');
                $this->log('Backup File Size:: ' . $fileSize);
                // Store the zip file in S3
                $backupService = $this->backup;
                $backupInS3 = $backupService->uploadBackupToS3($course->getId(), $uploadDir . $course->getUid() . '.zip');

                $this->log('Backup Download link: ' . $backupInS3['ObjectURL']);

                // Set in_progress to false for coursebackup
                $courseBackup->setInProgress(FALSE);
                $courseBackup->setCompleted(new DateTime());
                $courseBackup->setStart(null);
                $courseBackup->setSize($fileSize);
                $courseBackup->setS3Path($backupInS3['ObjectURL']);
                $em->persist($courseBackup);

                // Notify Users
                $backupService->notifyUsers($course);

            } else {
                $this->log('Course Backup Folder is empty. No files to backup.');

                // Set in_progress to false for coursebackup
                $courseBackup->setInProgress(FALSE);
                $courseBackup->setCompleted(new DateTime());
                $courseBackup->setStart(null);
                $em->persist($courseBackup);

            }

            //write the updates to the fields
            $em->flush();

            //Delete if folder exists
            if (file_exists($uploadDir.$course->getUid())) {
                $this->log( 'Deleting temporary folder ' . $uploadDir . $course->getUid() );

                $this->delTree($uploadDir.$course->getUid());
            }
            //Delete zip file if exists
            if (file_exists($uploadDir.$course->getUid().'.zip')) {
                $this->log( 'Deleting temporary zip ' . $uploadDir . $course->getUid() . '.zip' );
                unlink($uploadDir.$course->getUid().'.zip');
            }

        } catch(Exception $e) {
            $this->log('ERROR PROCESSING BACKUP for COURSE: ' . $course->getId());
            $this->log('ERROR:: ' . $e->getMessage());
        }
    }

    /**
     * Function to download a file provided that the source URl and destination path if given
     *
     * @param String        $destFolder         destination folder
     * @param String        $url                source URL of the file
     * @param String        $filename           output filename which would be the name of the file when saved on the disk
     */
    private function downloadFile( $destFolder, $url, $filename ) {
        $dest = $destFolder . "/" . $filename;

        $this->log( "Copying file as " . $dest );

        try {
            if (strlen(trim($url)) > 0) {
                if (copy($url, $dest)) {
                    $this->log('COPY SUCCESS - ' . $filename);
                } else {
                    $this->log('COPY FAIL - ' . $filename);
                }
            } else {
                $this->log('COPY FAIL - ' . $filename . ' URL is missing');
            }
        } catch (Exception $e) {
            $this->log('COPY FAIL - ' . $filename . ' Message: ' . $e->getMessage());
        }
    }

    /**
     * Function to obtain the file extension of a given URl of a file
     *
     * @param String         $url               url of file
     * @param String         $filename          intended filename
     *
     * @return String
     */
    private function guessFileName( $url, $filename ) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);

        $guesser = new MimeTypeExtensionGuesser();
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        $this->log( "Content Type: " . $contentType );

        $contentTypeItems = explode(";",$contentType) ;

        foreach( $contentTypeItems as $contentTypeItem ) {
            //We only need 1 proper file extension
            if( $guesser->guess( $contentTypeItem ) ) {
                $ext = $guesser->guess( $contentTypeItem );

                $filename = $filename . "." . $ext;
                break;
            }
        }

        return $filename;
    }

    /**
     * Function to save Download Task documents to the appropriate folder paths
     *
     * @param array         $data               array containing the Session information
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the session belongs to
     */
    private function downloadSubtasks( $data, $uploadDir, Course $course ) {
        foreach( $data as $task ) {
            if( count( $task["files"] ) ) {
                foreach( $task["files"] as $file ) {
                    if( isset( $file["toInclude"] ) && $file["toInclude"] ) {
                        $folder = $uploadDir . $course->getUid() . '/1) To-Do/' . $task["task_name"];
                        $url = $file["url"];
                        $filename = $file["filename"];

                        $this->downloadFile( $folder, $url, $filename );
                    }
                }
            }
        }
    }

    /**
     * Function to save Download Session documents to the appropriate folder paths
     *
     * @param array         $data               array containing the Session information
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the session belongs to
     */
    private function downloadSessionDocs( $data, $uploadDir, Course $course ) {
        foreach( $data as $session ) {
            if( count($session["required_readings"]) || count($session["recommended_readings"]) || count($session["optional_readings"]) || count($session["handouts"]) ) {
                $attachmentTypes = [["name" => "required_readings", "label" => "Required"], ["name" => "recommended_readings", "label" => "Recommended"], ["name" => "optional_readings", "label" => "Optional"], ["name" => "handouts", "label" => "Handouts"]];

                foreach( $attachmentTypes as $attachmentType ) {
                    foreach( $session[ $attachmentType["name"] ] as $file ) {
                        if( isset( $file["toInclude"] ) && $file["toInclude"] ) {
                            $folder = $uploadDir . $course->getUid() . '/3) Sessions/' . $session["session_name"] . "/" . $attachmentType["label"];
                            $url = $file["url"];
                            $filename = $file["filename"];

                            $this->downloadFile( $folder, $url, $filename );
                        }
                    }
                }
            }
        }
    }

    /**
     * Function to gather all the document information for Task
     *
     * @param Course        $course             Course Object where the session belongs to
     *
     * @return array
     */
    private function prepareTaskData( Course $course ) {
        $allTasks = [];

        try {
            /** @var Task $task */
            foreach($course->getTasks() as $task ) {
                if( $task->getPublished() ) {
                    $files = [];

                    foreach( $task->getSubtaskIds() as $subtask ) {
                        $subtaskObj = $this->entityManager
                            ->getRepository(Subtask::class)
                            ->findOneBy(['id' => $subtask]);

                        //0: attached file
                        //1: link
                        //2: text
                        if( $subtaskObj->getSubtaskType() == 0 ) {

                            $file = ["name" => $this->cleanName($subtaskObj->getTitle()), "filename" => $subtaskObj->getFilename(), "url" => $this->processFileDocument( $subtaskObj), "toInclude" => true];

                            $files[] = $file;
                        }
                    }

                    $taskDocs = ["task_name" => $this->cleanName($task->getTitle()), "files" => $files];

                    $allTasks[] = $taskDocs;
                }
            }
        } catch(Exception $e) {
            $this->log('ERROR PROCESSING BACKUP for COURSE: ' . $course->getId());
            $this->log('ERROR:: ' . $e->getMessage());
        }

        return $allTasks;
    }

    /**
     * Function to gather all the document information for Session
     *
     * @param Course        $course             Course Object where the session belongs to
     * @param DateTime      $now                today's date and time
     *
     * @return array
     */
    private function prepareSessionData( Course $course, $now ) {
        $allSessions = [];

        try {
            /** @var Session $courseSession */
            foreach ($course->getPublishedSessions() as $courseSession) {

                $session = $this->entityManager
                    ->getRepository(Session::class)
                    ->findOneBy(['id' => $courseSession->getId()]);

                $reqReadings    = [];
                $recReadings    = [];
                $optReadings    = [];
                $handouts       = [];

                foreach( $session->getAttachmentList() as $attachment ) {
                    $file = $attachment;

                    if( $attachment["type"] === "file_document" ) {
                        $attachmentObj = $this->entityManager
                            ->getRepository(FileDocument::class)
                            ->findOneBy(['id' => $attachment["id"]]);

                        $file["name"] = $this->cleanName($attachmentObj->getTitle());
                        $file["filename"] = $attachmentObj->getFilename();
                        $file["url"] = $this->processFileDocument( $attachmentObj );
                        $file["toInclude"] = true;

                    } elseif( $attachment["type"] === "linked_document" ) {
                        $attachmentObj = $this->entityManager
                            ->getRepository(LinkedDocument::class)
                            ->findOneBy(['id' => $attachment["id"]]);

                        $file["name"] = $this->cleanName($attachmentObj->getTitle());

                        $filename = $this->guessFileName( $attachmentObj->getUrl(), $file["name"] );
                        $file["filename"] = $filename;

                        $file["url"] = $attachmentObj->getUrl();
                        $file["toInclude"] = true;

                    } else {
                        $attachmentObj = $this->entityManager
                            ->getRepository(Link::class)
                            ->findOneBy(['id' => $attachment["id"]]);

                        //links and others (if any)
                        $file["toInclude"] = false;
                    }

                    if( $file["toInclude"] === true ) {
                        $params = ["session" => $session, "attachment" => $attachmentObj, "now" => $now];

                        if( ( $attachmentObj->getPublishAt() < $now ) || ( $this->isPublishedGroupSessionAttachment($params) ) ) {
                            //if file was published at group level, just log it
                            if( $this->isPublishedGroupSessionAttachment($params) ) {
                                $this->log( 'Item was not published at session-level published at group-level ' .
                                    json_encode(
                                        ["id" => $attachmentObj->getId(), "name" => $attachmentObj->getTitle()]
                                    )
                                );
                            }

                            switch ($attachment["document_type"]) {
                                case 0:
                                    $reqReadings[] = $file;
                                    break;
                                case 1:
                                    $recReadings[] = $file;
                                    break;
                                case 2:
                                    $handouts[] = $file;
                                    break;
                                case 5:
                                    $optReadings[] = $file;
                                    break;
                            }
                        }
                    }
                }

                $sessionName = $session->getName();
                if( $session->getAlternateSessionName() && trim((string) $session->getAlternateSessionName()) != "" ) {
                    $sessionName = $session->getAlternateSessionName();
                }

                $sessionDocs = ["session_name" => $this->cleanName($sessionName), "required_readings" => $reqReadings, "recommended_readings" => $recReadings, "optional_readings" => $optReadings, "handouts" => $handouts];

                $allSessions[] = $sessionDocs;

            }
        } catch(Exception $e) {
            $this->log('ERROR PROCESSING BACKUP for COURSE: ' . $course->getId());
            $this->log('ERROR:: ' . $e->getMessage());
        }

        return $allSessions;
    }

    /**
     * Main Function that manages the folder creation for the Course
     *
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the session belongs to
     * @param array         $allDocs            array the contains all document information for the course
     */
    private function prepareFolders( $uploadDir, Course $course, $allDocs ) {
        //Delete if folder exists
        if (file_exists($uploadDir.$course->getUid())) {
            $this->delTree($uploadDir.$course->getUid());
        }
        //Delete zip file if exists
        if (file_exists($uploadDir.$course->getUid().'.zip')) {
            unlink($uploadDir.$course->getUid().'.zip');
        }
        //Create a new empty folder
        mkdir($uploadDir.$course->getUid(), 0777, TRUE);

        $this->prepareSubtaskFolders( $uploadDir, $course, $allDocs );

        $this->prepareSessionFolders( $uploadDir, $course, $allDocs );
    }

    /**
     * Function that manages the profile book of the course's programme
     *
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the session belongs to
     */
    private function prepareProfileBook( $uploadDir, Course $course ) {
        $programme = $course->getProgramme();

        $bookPrefix = substr( $this->cleanName($programme->getName()), 0, 150);
        if( $programme->getStartDate() ) {
            $bookPrefix = $bookPrefix . "-" . $programme->getStartDate()->format("MY");
        }

        $programmeId = $programme->getId();

        $fullUrl = $this->s3->generateProfileBookTempUrl( $programmeId, "full" );
        $businessUrl = $this->s3->generateProfileBookTempUrl( $programmeId, "business" );

        $profileBookPath = $uploadDir . $course->getUid() . '/2) My Programme';

        if( $fullUrl !== "" || $businessUrl !== "" ) {
            mkdir($profileBookPath, 0777, TRUE);

            if( $fullUrl !== "" ) {
                $this->downloadFile($profileBookPath, $fullUrl, $bookPrefix . "-Full.pdf");
            }

            if( $businessUrl != "" ) {
                $this->downloadFile( $profileBookPath, $businessUrl ,$bookPrefix . "-Business.pdf" );
            }

        }
    }

    /**
     * Function that manages the Learning Journey programme
     *
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the session belongs to
     */
    private function prepareLearningJourney( $uploadDir, Course $course ) {
        $programme = $course->getProgramme();

        if( !$programme->getLearningJourney() ) {
            $this->log("Learning Journey not enable for this programme: ".$programme->getId());
            return;
        }

        $fullUrl = $this->s3->generateSignedURLForLearningJourney("My_Learning_Journey-" . $programme->getId() . ".pdf");
        $learningJourneyPath = $uploadDir . $course->getUid() . '/2) My Programme';

        if( $fullUrl !== "" ) {
            if ( !file_exists($learningJourneyPath) ) {
                mkdir($learningJourneyPath, 0777, TRUE);
            }
            
            if( $fullUrl !== "" ) {
                $this->downloadFile($learningJourneyPath, $fullUrl, "My Learning Journey.pdf");
            }

        }
    }

    /**
     * Function that manages the programme calendar of the course's programme
     *
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the session belongs to
     */
    private function prepareProgrammeCalendar( $uploadDir, Course $course ) {
        $programme = $course->getProgramme();

        $prefix = substr( $this->cleanName($programme->getName()), 0, 150);
        if( $programme->getStartDate() ) {
            $prefix = $prefix . "-" . $programme->getStartDate()->format("MY")."-Schedule";
        }

        $programmeId = $programme->getId();

        $url = $this->s3->generateCalendarTempUrl( $programmeId );

        $path = $uploadDir . $course->getUid() . '/2) My Programme';

        if( $url !== "" ) {
            mkdir($path, 0777, TRUE);

            if( $url !== "" ) {
                $this->downloadFile($path, $url, $prefix . ".pdf");
            }

        }
    }



    /**
     * Function that manages the programme calendar of the course's programme
     *
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the session belongs to
     */
    private function prepareSessionSheet( $uploadDir, Course $course ) {
        $programme = $course->getProgramme();

        $prefix = substr( $this->cleanName($programme->getName()), 0, 150);
        if( $programme->getStartDate() ) {
            $prefix = $prefix . "-" . $programme->getStartDate()->format("MY")."-Descriptions";
        }

        $programmeId = $programme->getId();

        $url = $this->s3->generateTempUrlItemFromStudyBackUp('session-files/'.$programmeId.'.pdf');

        $path = $uploadDir . $course->getUid() . '/2) My Programme';
        mkdir($path, 0777, TRUE);

        if( $url !== "" ) {

            if( $url !== "" ) {
                $this->downloadFile($path, $url, $prefix . ".pdf");
            }

        }
    }


    /**
     * Function to create the necessary folder for a Task and Subtask
     *
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the task belongs to
     * @param array         $allDocs            array the contains all document information for the course
     */
    private function prepareSubtaskFolders( $uploadDir, Course $course, $allDocs  ) {

        mkdir($uploadDir . $course->getUid() . '/1) To-Do', 0777, TRUE);

        if( isset($allDocs["tasks"]) ) {
            if( count($allDocs["tasks"]) ) {
                $hasSubtaskFile = false;
                foreach( $allDocs["tasks"] as $task ) {
                    if( count($task["files"]) ) {
                        $hasSubtaskFile = true;
                        break;
                    }
                }

                if( $hasSubtaskFile ) {

                    foreach ($allDocs["tasks"] as $task) {
                        if (count($task["files"])) {
                            mkdir($uploadDir . $course->getUid() . '/1) To-Do/' . $task["task_name"], 0777, TRUE);
                        }
                    }
                }
            }
        }
    }

    /**
     * Function to create the necessary folder for a Session
     *
     * @param String        $uploadDir          path of the temporary folder
     * @param Course        $course             Course Object where the session belongs to
     * @param array         $allDocs            array the contains all document information for the course
     */
    private function prepareSessionFolders( $uploadDir, Course $course, $allDocs  ) {
        if( isset($allDocs["sessions"]) ) {
            mkdir($uploadDir . $course->getUid() . '/3) Sessions', 0777, TRUE);
            foreach( $allDocs["sessions"] as $session ) {
                if( count($session["required_readings"]) || count($session["recommended_readings"]) || count($session["optional_readings"])  || count($session["handouts"]) ) {
                    $sessionFolder = $session["session_name"];

                    mkdir($uploadDir.$course->getUid().'/3) Sessions/'.$sessionFolder, 0777, TRUE);

                    if( count($session["required_readings"]) ) {
                        mkdir($uploadDir.$course->getUid().'/3) Sessions/'.$sessionFolder."/Required", 0777, TRUE);
                    }
                    if( count($session["recommended_readings"]) ) {
                        mkdir($uploadDir.$course->getUid().'/3) Sessions/'.$sessionFolder."/Recommended", 0777, TRUE);
                    }
                    if( count($session["optional_readings"]) ) {
                        mkdir($uploadDir.$course->getUid().'/3) Sessions/'.$sessionFolder."/Optional", 0777, TRUE);
                    }
                    if( count($session["handouts"]) ) {
                        mkdir($uploadDir.$course->getUid().'/3) Sessions/'.$sessionFolder."/Handouts", 0777, TRUE);
                    }
                }
            }
        }
    }


    /**
     * Function to get the Box Download URL of a given FileDocument
     *
     * @param FileDocument|Subtask $doc FileDocument Object
     *
     * @return String   Returns the Box Download URL of the FileDocument
     * @throws NotSupported
     */
    private function processFileDocument( $doc ) {
        $url = "";
        if ($doc::class == \Insead\MIMBundle\Entity\Subtask::class) {
            $subtaskFileId = $doc->getFileId();
            /** @var Subtask $subtaskDocument */
            $subtaskDocument = $this->entityManager
                ->getRepository(Subtask::class)
                ->findOneBy(['file_id' => $subtaskFileId]);

            if ($subtaskDocument){
                $url = $this->fileManager->generateProgrammeDocumentUrl($subtaskDocument->getAwsPath() . $subtaskDocument->getFilename());
            } else {
                $this->log("File not exist for Subtask ID: ".$doc->getId());
            }
        } else {
            $fileDocumentFileId = $doc->getFileId();
            /** @var FileDocument $fileDocument */
            $fileDocument = $this->entityManager
                ->getRepository(FileDocument::class)
                ->findOneBy(['file_id' => $fileDocumentFileId]);

            if ($fileDocument) {
                $pathKey = $this->fileManager->createPathKey($fileDocument->getSession()->getBoxFolderId(), $fileDocument->getSession()->getUid());
                $s3KeyPath = $pathKey["key_path"] . "/" . $fileDocument->getFilename();
                $url = $this->fileManager->generateProgrammeDocumentUrl($s3KeyPath);
            } else {
                $this->log("File not exist for FileDocument ID: ".$doc->getId());
            }
        }

        return $url;
    }

    /**
     * Function to delete the given folder
     *
     * @param String        $dir            path of Folder
     *
     * @return Boolean
     */
    private function delTree($dir) {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * Function to check if a given folder path if empty
     *
     * reference: http://stackoverflow.com/questions/7497733/how-can-use-php-to-check-if-a-directory-is-empty
     *
     * @param   String       $dir           path of Folder
     *
     * @return Boolean
     **/
    private function isDirEmpty($dir) {
        if (!is_readable($dir)) return NULL;
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Function to zip a given folder
     *
     * @param   String       $folderPath     path of Folder to zip
     * @param   String       $zipFileName    Path and name of the zip file output
     *
     * @return int
     **/
    private function zipFolder($folderPath, $zipFileName)
    {
        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        // Create recursive directory iterator
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file)
        {
            // Skip directories (they would be added automatically)


            if($file->isDir()){
                $filePath = $file->getRealPath();
                $relativePath = substr((string) $filePath, strlen($folderPath) - 10);
                if($relativePath!="")
                {
                    $zip->addEmptyDir($relativePath);
                }

            }

            if (!$file->isDir())
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr((string) $filePath, strlen($folderPath) - 10);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();
        return filesize($zipFileName);

    }

    /**
     *  Function to check if the attachment is published at the Group-level
     *
     * @param array $params an array of information containing Session Obj, Attachment Obj and today's datetime
     *
     * @return Boolean
     *
     * @throws NotSupported
     */
    private function isPublishedGroupSessionAttachment(array $params) {
        $session = $params['session'];
        $attachment = $params['attachment'];
        $now = $params['now'];

        $result = false;

        $criterion = [
            'session' => $session->getId(),
            'attachment_type' => $attachment->getAttachmentType(),
            'attachment_id' => $attachment->getId(),
        ];

        $attachments = $this->entityManager
            ->getRepository(GroupSessionAttachment::class)
            ->findBy($criterion);

        foreach ($attachments as $attachment_record) {
            if( $attachment_record->getPublishAt() < $now ) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     *   Function that returns the temp upload directory where uploaded documents
     *   are saved on disc before passing them on to Box API
     *
     **/
    private function getDocumentUploadDir()
    {
        return $this->rootDir . '/' . $this->uploadDir . '/';
    }

}
