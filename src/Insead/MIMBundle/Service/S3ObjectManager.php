<?php

namespace Insead\MIMBundle\Service;

use Exception;
use Aws\CloudFront\CloudFrontClient;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Psr\Log\LoggerInterface;


class S3ObjectManager
{
    /**
     * @var String AWS S3 Client variable
     */
    private $s3Client;

    /**
     * @var String AWS S3 Bucket
     */
    private $bucket;
    private $cdnBucket;

    public $backupBucket;
    private $backupUrl;
    private $backupKeyPair;
    private $contentType;
    private $customMedata;

    public function __construct(private $config, /**
     * @var LoggerInterface instance
     */
    private readonly LoggerInterface $logger)
    {
        $this->bucket  = $this->config['study_resource_bucket'];
        $this->cdnBucket  = $this->config['cdn_bucket'];

        // Load credentials from Container properties
        $credentials = ['key'    => $this->config['aws_access_key_id'], 'secret' => $this->config['aws_secret_key']];
        $region = $this->config['aws_region'];

        try {
            if( isset($this->config["symfony_environment"]) && $this->config["symfony_environment"] == 'dev' ) {
                // Instantiate the S3 client with AWS credentials
                $this->s3Client = new S3Client(['version' => 'latest', 'credentials' => $credentials, 'region' => $region]);

                $this->logger->info("Created S3 Client successfully. With credentials");
            } else {
                // Instantiate the S3 client without AWS credentials
                $this->s3Client = new S3Client(['version' => 'latest', 'region' => $region]);

                $this->logger->info("Created S3 Client successfully. Using Role");
            }

        } catch (Exception) {
            $this->logger->error("Unable to instantiate S3 Client.");
        }

        $this->backupBucket  = $this->config["aws_s3_bucket"];
        $this->backupUrl     = $this->config["backup_url"];
        $this->backupKeyPair = $this->config["backup_keypair_id"];
        $this->contentType   = "";
        $this->customMedata  = [];
    }

    /**
     * Handler to set contentType of S3 Object to upload
     * @param $contentType
     */
    public function setContentType($contentType){
        $this->contentType = $contentType;
    }

    /**
     * Handler to set custom medata of S3 Object to upload
     * @param $metadata
     */
    public function setCustomMetadata($metadata){
        $this->customMedata = $metadata;
    }

    /**
     * Upload File to S3 for a given path
     *
     * @param String        $path               Path where to upload the document
     * @param String        $content            String Content of the file to be uploaded
     * @param Boolean       $includeEnv         Should the path include a prefix of environment
     *
     * @return array        Array object containing the result of the request for Profile Book generation
     */
    public function uploadToS3($path, $content, $includeEnv = false, $overrideBucket = false)
    {
        $this->logger->info('Uploading file to S3.');

        $bucket = $this->bucket;
        if ($overrideBucket) {
            $bucket = $overrideBucket;
        }

        $key = $this->setEnvPath($path, $bucket, $includeEnv);
        
        try {
            $itemData = ['Bucket'       => $bucket, 'Key'          => $key, 'Body'         => $content, 'StorageClass' => 'REDUCED_REDUNDANCY'];

            if (strlen(trim($this->contentType)) > 1) {
                $itemData['ContentType'] = trim($this->contentType);
            }

            if (count($this->customMedata) > 0){
                $itemData['Metadata'] = $this->customMedata;
            }

            $result = $this->s3Client->putObject($itemData);

            $this->logger->info('Successfully uploaded to S3 ('.$key.').');
            $date = new \DateTime();

            return ["status" => "success", "data" => ["url" => $result['ObjectURL'], "bucket" => $bucket, "key" => $key, "timestamp" => "S3" . $date->getTimestamp() . $this->generateRandomString()]];
        } catch (S3Exception $e) {
            $this->logger->error( "Error uploading file " . $key . ": " . $e->getMessage() );
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Upload File to S3 for a given Stream
     *
     * @param String $path Path where to upload the document
     * @param String $file_path Source path of the file to be uploaded
     * @param Boolean $includeEnv Should the path include a prefix of environment
     *
     * @return array        Array object containing the result of the request for Profile Book generation
     * @throws Exception
     */
    public function uploadToS3WithPath($path, $file_path, $includeEnv = false)
    {
        $this->logger->info('Uploading file to S3.');
        $key = $this->setEnvPath($path, $this->bucket, $includeEnv);
        try {
            $result = $this->s3Client->putObject(['Bucket'       => $this->bucket, 'Key'          => $key, 'SourceFile'   => $file_path, 'StorageClass' => 'REDUCED_REDUNDANCY']);
            $this->logger->info('Successfully uploaded to S3 ('.$key.').');
            $date = new \DateTime();
            return ["status" => "success", "data" => ["url" => $result['ObjectURL'], "bucket" => $this->bucket, "key" => $key, "timestamp" => "S3" . $date->getTimestamp() . $this->generateRandomString()]];
        } catch (S3Exception $e) {
            $this->logger->error( "Error uploading file " . $key . ": " . $e->getMessage() );
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Remove a File from S3 for a given path
     *
     * @param String        $path               Path where to upload the document
     * @param Boolean       $includeEnv         Should the path include a prefix of environment
     *
     * @return array        Array object containing the result of the request for Profile Book generation
     */
    public function removeFromS3($path, $includeEnv = false, $overrideBucket = false)
    {
        $this->logger->info('Removing file to S3.');
        $bucket = $this->bucket;
        if ($overrideBucket) {
            $bucket = $overrideBucket;
        }

        $key = $this->setEnvPath($path, $bucket, $includeEnv);

        try {
            $result = $this->s3Client->deleteObject(['Bucket'       => $bucket, 'Key'          => $key]);

            return ["status" => "success", "data" => ["url" => $result['ObjectURL'], "bucket" => $bucket, "key" => $key]];
        } catch (S3Exception $e) {
            $this->logger->error( "Error deleting file " . $key . ": " . $e->getMessage() );
            return ["error" => $e->getMessage()];
        }
    }

    /**
     * Retrieve a File from S3 for a given path
     *
     * @param String        $path               Path where to upload the document
     * @param Boolean       $includeEnv         Should the path include a prefix of environment
     *
     * @return array        Array object containing the result of the request for Profile Book generation
     */
    public function getFromS3($path, $includeEnv = false)
    {
        $this->logger->info('Retrieving file from S3.');

        $key = $this->setEnvPath($path, $this->bucket, $includeEnv);

        try {
            $result = $this->s3Client->getObject(['Bucket'                     => $this->bucket, 'Key'                        => $key]);
        } catch (S3Exception $e) {
            $this->logger->error( "Error retrieving file from S3 for " . $key . ": " . $e->getMessage() );
            $result =  ["error" => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Retrieve a S3 objects by prefix
     *
     * @param String        $path               Path where to upload the document
     * @param Boolean       $includeEnv         Should the path include a prefix of environment
     *
     * @return array        Array object containing the result of the request for Profile Book generation
     */
    public function getFromS3WithPrefix($path, $includeEnv = false)
    {
        $this->logger->info('Retrieving file from S3.');

        $key = $this->setEnvPath($path, $this->bucket, $includeEnv);

        try {
            $result = $this->s3Client->listObjects(['Bucket' => $this->bucket, 'Prefix' => $key]);
        } catch (S3Exception $e) {
            $this->logger->error( "Error retrieving file from S3 for " . $key . ": " . $e->getMessage() );
            $result =  ["error" => $e->getMessage()];
        }

        return $result;
    }

    public function generateProfileBookTempUrl($programmeId,$type="full",$peoplesoftId="") {

        $signedURL = "";

        $env = $this->config["symfony_environment"] . "/";
        $key = "profile-books/";
        $key = $key . $type . "/";
        $key = $key . "INSEAD Prog" . $programmeId . ".pdf";

        $timestamp = strtotime("+1440 minutes");
        $cloudFrontDomain = $this->backupUrl;
        $keyPairId = $this->backupKeyPair;
        $additionalParameters = '';

        if( $peoplesoftId ) {
            $additionalParameters = "?foruser=" . urlencode((string) $peoplesoftId);
        }

        try {
            if( $this->s3Client->doesObjectExist( $this->backupBucket, $env . $key ) ) {
                $this->logger->info("Generating Profile Book [" . $type . "] for Prog" . $programmeId);

                $cloudFrontClient = new CloudFrontClient(
                    ['version' => 'latest', 'region' => $this->config["aws_region"]]
                );

                $signedURL = $cloudFrontClient->getSignedUrl(
                    ['url' => "$cloudFrontDomain/$key$additionalParameters", 'expires' => $timestamp, 'private_key' => '../certs/pk-CloudFront.pem', 'key_pair_id' => $keyPairId]
                );
            } else {
                $this->logger->info("Profile Book [" . $type . "] was not found for Prog" . $programmeId);
            }
        } catch (S3Exception $e) {
            $this->logger->error( "Error generating SignedURL for " . $key . ": " . $e->getMessage() );
        }

        return $signedURL;

    }

    public function getObjectLastUpdatedDate($programmeId,$type="full") {

        $modificationDate = "";

        $env = $this->config["symfony_environment"] . "/";
        $key = "profile-books/";
        $key = $key . $type . "/";
        $key = $key . "INSEAD Prog" . $programmeId . ".pdf";

        try {
            if( $this->s3Client->doesObjectExist( $this->backupBucket, $env . $key ) ) {
                $this->logger->info("Checking timestamp of Profile Book [" . $type . "] for Prog" . $programmeId);

                $obj = $this->s3Client->getObject(
                    ["Bucket" => $this->backupBucket, "Key" => $env . $key]
                );
                $modificationDate = $obj["LastModified"];

            } else {
                $this->logger->info("Profile Book [" . $type . "] was not found for Prog" . $programmeId);
            }
        } catch (S3Exception $e) {
            $this->logger->error( "Error accessing object " . $env . $key . ": " . $e->getMessage() );
        }

        return $modificationDate;

    }

    public function generateCalendarTempUrl($programmeId,$peoplesoftId="") {

        $signedURL = "";

        $env = $this->config["symfony_environment"] . "/";
        $key = "week-calendar/";
        $key = $key . "" . $programmeId . ".pdf";

        $timestamp = strtotime("+1440 minutes");
        $cloudFrontDomain = $this->backupUrl;
        $keyPairId = $this->backupKeyPair;
        $additionalParameters = '';

        if( $peoplesoftId ) {
            $additionalParameters = "?foruser=" . urlencode((string) $peoplesoftId);
        }

        try {
            if( $this->s3Client->doesObjectExist( $this->backupBucket, $env . $key ) ) {
                $this->logger->info("Generating Calendar for Prog" . $programmeId);

                $cloudFrontClient = new CloudFrontClient(
                    ['version' => 'latest', 'region' => $this->config["aws_region"]]
                );

                $signedURL = $cloudFrontClient->getSignedUrl(
                    ['url' => "$cloudFrontDomain/$key$additionalParameters", 'expires' => $timestamp, 'private_key' => '../certs/pk-CloudFront.pem', 'key_pair_id' => $keyPairId]
                );
            } else {
                $this->logger->info("Calendar was not found for Prog" . $programmeId);
            }
        } catch (S3Exception $e) {
            $this->logger->error( "Error generating SignedURL for " . $key . ": " . $e->getMessage() );
        }
        return $signedURL;
    }

    public function generateTempUrlItemFromStudyBackUp($path) {

        $signedURL = "";

        $timestamp = strtotime("+1440 minutes");
        $cloudFrontDomain = $this->backupUrl;
        $keyPairId = $this->backupKeyPair;

        try {
            if( $this->s3Client->doesObjectExist( $this->backupBucket, $this->config["symfony_environment"]."/".$path ) ) {
                $this->logger->info("Generating Temp url for " . $path);

                $cloudFrontClient = new CloudFrontClient(
                    ['version' => 'latest', 'region' => $this->config["aws_region"]]
                );

                $signedURL = $cloudFrontClient->getSignedUrl(
                    ['url' => "$cloudFrontDomain/$path", 'expires' => $timestamp, 'private_key' => '../certs/pk-CloudFront.pem', 'key_pair_id' => $keyPairId]
                );
            } else {
                $this->logger->info("Not found in S3: " . $path);
            }
        } catch (S3Exception $e) {
            $this->logger->error( "Error generating signed url for " . $path. ": " . $e->getMessage() );
        }

        return $signedURL;

    }

    public function generateSignedURLForDocumentRepo($key){
        $path = $this->config["symfony_environment"] . '/document-repository/' . $key;
        return $this->generateSignedURL($this->bucket, $path, $key, $this->backupUrl, strtotime("+720 minutes"), $this->backupKeyPair, '../certs/pk-CloudFront.pem');
    }

    public function generateSignedURLForLearningJourney($key){
        $path = $this->config["symfony_environment"] . '/learning-journey/' . $key;
        $keyPath = 'learning-journey/' . $key;
        return $this->generateSignedURL($this->backupBucket, $path, $keyPath, $this->backupUrl, strtotime("+720 minutes"), $this->backupKeyPair, '../certs/pk-CloudFront.pem');
    }

    private function generateSignedURL($bucket, $keyToCheckInBucket, $pathURL, $cloudFrontDomain, $timestamp, $keyPairID, $privateKey){
        try {
            if( $this->s3Client->doesObjectExist( $bucket, $keyToCheckInBucket ) ) {
                $this->logger->info("Generating Signed url for: ".$keyToCheckInBucket);

                $cloudFrontClient = new CloudFrontClient(
                    ['version' => 'latest', 'region' => $this->config["aws_region"]]
                );

                return $cloudFrontClient->getSignedUrl(
                    ['url' => "$cloudFrontDomain/$pathURL", 'expires' => $timestamp, 'private_key' => $privateKey, 'key_pair_id' => $keyPairID]
                );
            } else {
                $this->logger->info("Object ($keyToCheckInBucket) not existing in bucket: $bucket");
                return "";
            }
        } catch (S3Exception $e) {
            $this->logger->info( "Exception while generating SignedURL for ($pathURL): " . $e->getMessage() );
            return "";
        }
    }

    public function getObjectDetailsFromStudyBackUp($path) {
        $path = $this->config["symfony_environment"]."/".$path;
        $obj = null;
        try {
            if( $this->s3Client->doesObjectExist( $this->backupBucket, $path ) ) {
                $this->logger->info("Checking item: " . $path);

                $objS3 = $this->s3Client->getObject(
                    ["Bucket" => $this->backupBucket, "Key" => $path]
                );

                if ($objS3){
                    $this->logger->info('Found in S3: '.$path);
                    $obj["LastModified"] = $objS3["LastModified"];
                    $obj["VersionId"]    = $objS3["VersionId"];
                    $obj["Metadata"]     = $objS3["Metadata"];
                    $obj["headers"]      = $objS3["headers"];
                }
            } else {
                $this->logger->info("Not found in S3: " . $path);
            }
        } catch (S3Exception $e) {
            $this->logger->error( "Error accessing object " . $path. ": " . $e->getMessage() );
        }

        return $obj;

    }

    public function checkItemFromStudyBackUp($path){
        $path = $this->config["symfony_environment"]."/".$path;
        return $this->s3Client->doesObjectExist( $this->backupBucket, $path );
    }

    public function checkIfCdnItemExists($path, $includeEnv = false)
    {
        return $this->s3Client->doesObjectExist( $this->cdnBucket, $path );
    }

    public function checkIfResourceItemExists($path, $includeEnv = false)
    {
        if ($includeEnv){
            $path = $this->config["symfony_environment"]."/".$path;
        }

        return $this->s3Client->doesObjectExist( $this->bucket, $path );
    }

    public function getCdnItem($path)
    {
        $obj = [];

        try {
            if( $this->s3Client->doesObjectExist( $this->cdnBucket, $path ) ) {
                $this->logger->info("Retrieve item from CDN bucket: " . $this->cdnBucket . "/" . $path);

                $obj = $this->s3Client->getObject(
                    ["Bucket" => $this->cdnBucket, "Key" => $path]
                );

            } else {
                $this->logger->info("Cdn Item was not found" . $this->cdnBucket . "/" . $path);
            }
        } catch (S3Exception $e) {
            $this->logger->error( "Error accessing item " . $path . ": " . $e->getMessage() );
        }

        return $obj;
    }

    public function uploadImageToCDNBucket($path, $file_path, $includeEnv = false)
    {
        $this->logger->info('Uploading file to CDN S3.');

        $key = $this->setEnvPath($path, $this->cdnBucket, $includeEnv);

        try {
            $result = $this->s3Client->putObject(['Bucket'       => $this->cdnBucket, 'Key'          => $key, 'SourceFile'   => $file_path, 'StorageClass' => 'STANDARD']);

            return ["status" => "success", "data" => ["url" => $result['ObjectURL'], "bucket" => $this->cdnBucket, "key" => $key]];
        } catch (S3Exception $e) {
            $this->logger->error( "Error uploading file " . $key . ": " . $e->getMessage() );
            return ["error" => $e->getMessage()];
        }
    }

    public function copyExistingItemToS3($path, $file_path, $includeEnv = false)
    {
        $this->logger->info('Copy existing file to S3.');
        $key       = $this->setEnvPath($path, $this->bucket, $includeEnv);
        if ($includeEnv) {
            $file_path = $this->bucket . "/" . $this->config["symfony_environment"]."/".$file_path;
        } else {
            $file_path = $this->bucket . "/" .$file_path;
        }

        try {
            $result = $this->s3Client->copyObject(['Bucket'       => $this->bucket, 'Key'          => $key, 'CopySource'   => $this->s3Client::encodeKey($file_path), 'StorageClass' => 'REDUCED_REDUNDANCY']);
            $this->logger->info('Successfully copy an item to S3 ('.$key.').');
            $date = new \DateTime();
            return ["status" => "success", "data" => ["url" => $result['ObjectURL'], "bucket" => $this->bucket, "key" => $key, "timestamp" => "S3" . $date->getTimestamp() . $this->generateRandomString()]];
        } catch (S3Exception $e) {
            $this->logger->error( "Error copying file " . $key . ": " . $e->getMessage() );
            return ["error" => $e->getMessage()];
        }
    }

    /** Function to randomly generate a string
     *
     * @param int $length of the randomString
     *
     * @return String
     */
    public function generateRandomString($length = 4)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function setEnvPath($path, $bucket, $includeEnv = false)
    {
        $env = $this->config["symfony_environment"];

        if( $env !== '' && $includeEnv ) {
            $key = $env . "/" . $path;
        } else {
            $key = $path;
        }

        $this->logger->info('bucket: ' . $bucket);
        $this->logger->info('key: ' . $key);
        return $key;
    }
}
