<?php

namespace esuite\MIMBundle\Service;

use Exception;

use Aws\Sns\SnsClient;

use LightSaml\Meta\ParameterBag;
use Psr\Log\LoggerInterface;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use esuite\MIMBundle\Entity\Course;

class edotNotify
{

    /**
     * @var String AWS SNS Client variable
     */
    private $snsClient;

    /**
     * @var String AWS Service region
     */
    private static $AWS_REGION;

    /**
     * @var String AWS Credentials array
     */
    private static $AWS_CREDENTIALS;

    /**
     * @var String AWS SNS Platform Application ARN string
     */
    private static $SNS_PLATFORM_APP_ARN;

    private static $PLATFORM_ENV;

    /**
     * @var String LogUUID to be added at the beginning of logs
     */
    protected $logUuid="";

    protected $configuration;

    public function __construct(ParameterBagInterface $parameterBag, /**
     * @var LoggerInterface instance
     */
    private readonly LoggerInterface $logger)
    {
        $config = $parameterBag->get('edot.notify.config');
        $this->configuration = $config;

        // Load credentials from Container properties
        self::$AWS_CREDENTIALS = ['key'    => $config['aws_access_key_id'], 'secret' => $config['aws_secret_key']];
        self::$AWS_REGION = $config['aws_region'];

        self::$SNS_PLATFORM_APP_ARN = $config['aws_sns_platform_app_arn'];

        self::$PLATFORM_ENV = $config['symfony_environment'];

        $this->snsClient = null;

        try {
            if( isset($config["symfony_environment"]) && $config["symfony_environment"] == 'dev' ) {
                // Instantiate the SNS client with AWS credentials
                $this->snsClient = new SnsClient(['version' => 'latest', 'credentials' => self::$AWS_CREDENTIALS, 'region' => self::$AWS_REGION]);

                $this->logger->info("$this->logUuid Created SNS Client successfully. With credentials");
            } else {
                // Instantiate the SNS client without AWS credentials
                $this->snsClient = new SnsClient(['version' => 'latest', 'region' => self::$AWS_REGION]);
            }

        } catch (Exception) {
            $this->logger->info("$this->logUuid Unable to instantiate SNS Client.");
        }

    }

    /**
     * return the environment
     *
     * @return mixed
     */
    public function getEnvironment(){
        return $this->configuration["kernel_environment"];
    }

    /**
     * Get devices IDs for Students on a course
     *
     * @param Course $course   The course
     *
     * @return array
     */
    protected function deviceIdsFromCourse(Course $course)
    {
        $deviceIds = [];
        if($course->getPublished()) {
            $deviceIds = $course->getSubscribedUserDevices();
            $this->logger->info("$this->logUuid COURSE IS PUBLISHED. SO SENDING PUSH NOTIFICATIONS to " . sizeof($deviceIds). " devices !");
        } else {
            $this->logger->info("$this->logUuid COURSE IS NOT PUBLISHED. SO NOT SENDING ANY PUSH NOTIFICATIONS!!");
        }

        return $deviceIds;
    }

    /**
     * edotNotify#message
     * Send a notification message to students on a course
     *
     * @param Course                                $course   The course
     * @param String                                $type     The entity type of the notification
     * @param String                                $content  The notification message
     */
    public function message(Course $course, $type="", $content = 'edotnotify')
    {
        $this->logger->info("$this->logUuid Request to send notifications for course: " . $course->getId());
        $deviceIds = $this->deviceIdsFromCourse($course);

        if ($this->snsClient && count($deviceIds)) {
            // Get all unique device ids as there may be some duplicates
            $deviceIds = array_unique($deviceIds, SORT_STRING);
            foreach ($deviceIds as $deviceId) {
                try {
                    $this->logger->info("$this->logUuid Sending Notifications [$type] using SNS SDK to device::" . $deviceId);
                    //Create Platform Endpoint for a device - this acts as a connection object to the device
                    $endpoint = $this->snsClient->createPlatformEndpoint(['PlatformApplicationArn' => self::$SNS_PLATFORM_APP_ARN, 'Token' => $deviceId, 'CustomUserData' => 'PLATFORM_ENDPOINT for device - '.$deviceId]);
                    $this->logger->info("$this->logUuid ENDPOINT OBJECT CREATED: " . $endpoint->__toString());

                    $this->logger->info("$this->logUuid ENDPOINT Information: " . $endpoint->get('EndpointArn'));
                    $this->snsClient->setEndpointAttributes(['EndpointArn' => $endpoint->get('EndpointArn'), 'Attributes' => ['Enabled' => 'true']]);

                    //Publish Notification message - this step will push Notification message using the endpoint
                    $this->snsClient->publish([
                        'TargetArn' => $endpoint->get('EndpointArn'),
                        //'Message' => '{"default": "'. $content .'"}',
                        'Message' => json_encode(
                            ['APNS' => json_encode(
                                ["aps" => ["alert" => $content, "content-available" => 1]]
                            )]
                        ),
                        //!!PV 20160318 TTL reference:
                        //    http://stackoverflow.com/questions/27168896/how-to-set-ttl-attribute-in-sns-with-publish-method-php-sdk
                        //    http://docs.aws.amazon.com/sns/latest/dg/sns-ttl.html
                        'MessageAttributes' => ['AWS.SNS.MOBILE.APNS.TTL' => ['DataType' => 'Number', 'StringValue' => '43200']],
                        'MessageStructure' => 'json',
                    ]);
                    
                    $this->logger->info("$this->logUuid APNS NOTIFICATION ($deviceId) SENT THROUGH SNS: " . ($content ?: "[INVISIBLE]"));
                } catch (Exception $e) {
                    $this->logger->error("$this->logUuid SNS ERROR CREATING PUSH NOTIFICATION: " . $e->getMessage());
                    $this->logger->info("$this->logUuid Exception Code:: \n" . $e->getCode());
                    $this->logger->info("$this->logUuid Exception Trace:: \n" . $e->getTraceAsString());

                    if (self::$PLATFORM_ENV === 'dev') break;
                    else continue;
                }
            }
        }
    }

    /**
     * Set LogUuid from a given request object
     *     parses the contents of the request object to obtain the access token
     *
     * @param Request $request object where the access token is included
     */
    public function setLogUuid(Request $request) {
        $headers = $request->headers;

        $bearerHeader = "Bearer ";

        $authHeader = $headers->get('Authorization');

        $authPrefix = substr( (string) $authHeader, 0, strlen($bearerHeader) );
        $authToken = substr( (string) $authHeader, strlen($bearerHeader) );

        if( strcmp($authPrefix,$bearerHeader) == 0 ) {
            $this->logUuid = "[" . substr($authToken,0,8) ."..." . substr($authToken,-8) ."]";
        }
    }

    /**
     * Set LogUuid from a given string
     *
     * @param String $logUuid assumes that the loguuid was already obtained as a string
     */
    public function setLogUuidWithString($logUuid) {
        $this->logUuid = "[" . $logUuid ."]";
    }
}
