<?php

namespace esuite\MIMBundle\Service;

use Exception;
use Doctrine\ORM\EntityManager;

use Aws\S3\S3Client;
use Aws\Ses\SesClient;
use Aws\CloudFront\CloudFrontClient;

use esuite\MIMBundle\Entity\CourseBackupEmail;
use Psr\Log\LoggerInterface;

use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\CourseBackup;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class edotCourseBackup
{
    /**
     * @var String AWS S3 Client variable
     */
    private $s3Client;

    /**
     * @var String AWS SES Client variable
     */
    private $sesClient;

    /**
     * @var String AWS Service region
     */
    private static $AWS_REGION;

    /**
     * @var String AWS Credentials array
     */
    private static $AWS_CREDENTIALS;

    /**
     * @var String AWS S3 Bucket
     */
    private static $S3_BUCKET;

    /**
     * @var String Backup URL
     */
    private static $BACKUP_URL;

    /**
     * @var String Backup Keypair ID
     */
    private static $BACKUP_KEYPAIR_ID;
    private $config;

    public function __construct(private readonly EntityManager $entityManager, ParameterBagInterface $parameterBag, /**
     * @var LoggerInterface instance
     */
    private readonly LoggerInterface $logger)
    {
        $config = $parameterBag->get('edot.backup.config');
        $this->config           = $config;

        self::$S3_BUCKET  = $config['aws_s3_bucket'];

        // Load credentials from Container properties
        self::$AWS_CREDENTIALS = ['key'    => $this->config['aws_access_key_id'], 'secret' => $this->config['aws_secret_key']];
        self::$AWS_REGION = $config['aws_region'];

        try {
            if( isset($this->config["symfony_environment"]) && $this->config["symfony_environment"] == 'dev' ) {
                // Instantiate the S3 client with AWS credentials
                $this->s3Client = new S3Client([
                    //'version' => '2006-03-01',
                    'version' => 'latest',
                    'credentials' => self::$AWS_CREDENTIALS,
                    'region' => self::$AWS_REGION,
                ]);

                $this->logger->info("Created S3 Client successfully. With credentials");
            } else {
                // Instantiate the S3 client without AWS credentials
                $this->s3Client = new S3Client([
                    //'version' => '2006-03-01',
                    'version' => 'latest',
                    'region' => self::$AWS_REGION,
                ]);
            }

        } catch (Exception) {
            $this->logger->info("Unable to instantiate S3 Client.");
        }

        try {
            if( isset($this->config["symfony_environment"]) && $this->config["symfony_environment"] == 'dev' ) {
                // Instantiate the SES client with AWS credentials
                $this->sesClient = new SesClient(['version' => 'latest', 'credentials' => self::$AWS_CREDENTIALS, 'region' => self::$AWS_REGION]);

                $this->logger->info("Created SES Client successfully. With credentials");
            } else {
                // Instantiate the SES client without AWS credentials
                $this->sesClient = new SesClient(['version' => 'latest', 'region' => self::$AWS_REGION]);
            }

        } catch (Exception) {
            $this->logger->info("Unable to instantiate SES Client.");
        }

        self::$BACKUP_URL = $config['backup_url'];
        self::$BACKUP_KEYPAIR_ID = $config['backup_keypair_id'];



    }

    /**
     * MIMCourseBackup#copyBackupToS3
     * Copy a course backup file to Amazon S3 location and return the URL using which students can download the backup file
     *
     * @param integer       $courseId           id of the course that was backed up
     * @param String        $backupFilePath     Path of the zip file that needs to be copied over to an S3 location
     *
     * @return String   Returns the URl of backup file on S3 that will be accessible publicly
     */
    public function uploadBackupToS3($courseId, $backupFilePath)
    {
        $this->logger->info('Uploading file to S3.');

        $env = $this->config["symfony_environment"];

        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if( $env !== '' ) {
            $key = $env . "/" . "esuite Prog" . $course->getProgrammeId() . "-" . "Course" . $course->getId() . '.zip';
        } else {
            $key = "esuite Prog" . $course->getProgrammeId() . "-" . "Course" . $course->getId() . '.zip';
        }

        // Upload backup file.
        $result = $this->s3Client->putObject([
            'Bucket'       => self::$S3_BUCKET,
            'Key'          => $key,
            'SourceFile'   => $backupFilePath,
            'ContentType'  => 'application/octet-stream',
            //'ACL'          => 'public-read',
            'StorageClass' => 'REDUCED_REDUNDANCY',
        ]);
        return $result;
    }

    public function generateTempUrl($courseId,$requestorEmail='') {

        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        $item = "esuite Prog" . $course->getProgrammeId() . "-" . "Course" . $course->getId() . '.zip';
        $timestamp = strtotime("+1440 minutes");
        $cloudFrontDomain = self::$BACKUP_URL;
        $keyPairId = self::$BACKUP_KEYPAIR_ID;
        $additionalParameters = '';

        if( $requestorEmail ) {
            $additionalParameters = "?email=" . urlencode((string) $requestorEmail);
        }

        $cloudFrontClient = new CloudFrontClient(
            ['version' => 'latest', 'region' => self::$AWS_REGION]
        );

        $signedURL = $cloudFrontClient->getSignedUrl(
            ['url' => "$cloudFrontDomain/$item$additionalParameters", 'expires' => $timestamp, 'private_key' => '../certs/pk-CloudFront.pem', 'key_pair_id' => $keyPairId]
        );

        return $signedURL;

    }

    public function notifyUsers(Course $course)
    {
        // Get the list of users waiting for Backup for this course
        $coursebackupEmails = $this->entityManager->getRepository(CourseBackupEmail::class)->findBy(['course' => $course]);
        $this->logger->info('Emails::' . count($coursebackupEmails));
        foreach($coursebackupEmails as $coursebackupEmail) {

            $this->sendEmail($course, $coursebackupEmail->getUserEmail());
            // Delete the entry after sending email
            $this->entityManager->remove($coursebackupEmail);
        }

        $this->entityManager->flush();
    }

    private function sendEmail(Course $course, $emailId)
    {
        $msg = [];
        $msg['Source'] = $this->config['aws_ses_from_email'];

        $env            = $this->config["symfony_environment"];
        $edotWebUrl    = $this->config["edot_weburl"];
        $ccList         = $this->config["aws_ses_cc_email"];

        //ToAddresses must be an array; if environment is prd or prd2, use the real email, otherwise send to sender
        if( str_contains((string) $env,'prd') ) {
            $msg['Destination']['ToAddresses'][]        = $emailId;

            $msg['Message']['Subject']['Data']          = "Your esuite programme documents are ready";
        } else {
            $msg['Destination']['ToAddresses'][]        = "appdev.testing@esuite.edu";
            if( $ccList ) {
                $msg['Destination']['CcAddresses'] = explode(",", (string) $ccList);
            }

            $msg['Message']['Subject']['Data']          = "Your " . strtoupper((string) $env) . "esuite programme documents are ready";
        }
        $msg['Destination']['BccAddresses'][] = "appdev.testing@esuite.edu";

        $msg['Message']['Subject']['Charset'] = "UTF-8";

        $htmlContent        = "";
        $htmlContent        = $htmlContent . "Dear Participant, ";
        $htmlContent        = $htmlContent . "<br/><br/>You are receiving this email because you requested to download all documents from edot@esuite. ";
        $htmlContent        = $htmlContent . "<br/><br/>Your zip file is now ready, please log into edot@esuite to access it. ";
            $htmlContent    = $htmlContent . "<br/>Go to " . $edotWebUrl . " <strong>and access the Menu to download all documents.</strong>";
        $htmlContent        = $htmlContent . "<br/><br/>Please note that you cannot reply to this email which has been generated automatically. ";
            $htmlContent    = $htmlContent . "<br/>If you have any questions please contact your programme coordinator. ";
        $htmlContent        = $htmlContent . "<br/><br/>Best regards, ";
        $htmlContent        = $htmlContent . "<br/>esuite edot Team";

        $rawContent = $htmlContent;
        $rawContent = str_replace("<br/>","",$rawContent);

        if( str_contains((string) $env,'prd') ) {
            $msg['Message']['Body']['Text']['Data'] =  $rawContent;
            $msg['Message']['Body']['Text']['Charset'] = "UTF-8";
            $msg['Message']['Body']['Html']['Data'] = $htmlContent;
            $msg['Message']['Body']['Html']['Charset'] = "UTF-8";
        } else {
            $msg['Message']['Body']['Text']['Data'] = "This " . $env . " email was intended to " . $emailId . "  " . $rawContent;
            $msg['Message']['Body']['Text']['Charset'] = "UTF-8";
            $msg['Message']['Body']['Html']['Data'] = "This " . $env . " email was intended to " . $emailId . "<br/><br/>" . $htmlContent;
            $msg['Message']['Body']['Html']['Charset'] = "UTF-8";
        }

        try{
             $result = $this->sesClient->sendEmail($msg);

             //save the MessageId which can be used to track the request
             $msg_id = $result->get('MessageId');
             $this->logger->info("Email notification sent : " .
                json_encode(["msg_id" => $msg_id, "course_id" => $course->getId(), "intended_for" => $emailId])
             );

        } catch (Exception $e) {
             //An error occurred and the email did not get sent
             $this->logger->error('ERROR sending Email notification for course: ' . $course->getId() . ' to email: ' . $emailId);
             $this->logger->error('ERROR MESSAGE:: ' . $e->getMessage());
        }
    }

    public function updateCoursebackup(Course $course)
    {
        $coursebackup = $this->entityManager->getRepository(CourseBackup::class)->findOneBy(['course' => $course]);

        if (!$coursebackup) {
            $coursebackup = new CourseBackup();
            $coursebackup->setCourse($course);
        }

        $coursebackup->setInProgress(TRUE);
        $this->entityManager->persist($coursebackup);
        $this->entityManager->flush();

    }
}
