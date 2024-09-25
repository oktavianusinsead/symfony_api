<?php

namespace esuite\MIMBundle\Service\File;

use Doctrine\ORM\EntityManager;
use Exception;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\edotNotify;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use \esuite\MIMBundle\Service\Manager\Base as baseServiceManager;

class Base
{
    /**
     * @var String AWS S3 Client variable
     */
    protected $s3ObjectManager;

    protected $baseServiceManager;
    protected $config;
    protected $notify;
    protected $em;

    /**
     * @var LoggerInterface instance
     */
    protected $logger;

    /**
     * @var string
     */
    private $logUuid;

    public function __construct(array $config, LoggerInterface $logger, edotNotify $notify, EntityManager $em, S3ObjectManager $s3Object, baseServiceManager $baseServiceManager)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->notify = $notify;
        $this->em = $em;
        $this->s3ObjectManager = $s3Object;
        $this->baseServiceManager = $baseServiceManager;
    }

    /**
     * Function to remember the logUuid
     */
    public function setLogUuidFromRequest(RequestStack $requestStack) {
        if ($requestStack->getCurrentRequest()) {
            $request = $requestStack->getCurrentRequest();

            $headers = $request->headers;

            $bearerHeader = "Bearer ";

            $authHeader = $headers->get('Authorization');

            $authPrefix = substr((string) $authHeader, 0, strlen($bearerHeader));
            $authToken = substr((string) $authHeader, strlen($bearerHeader));

            if (strcmp($authPrefix, $bearerHeader) == 0) {
                $this->logUuid = "[" . substr($authToken, 0, 8) . "..." . substr($authToken, -8) . "]";
            }
        }
    }

    /**
     * Function that logs a message, prefixing the Class and function name to help debug
     *
     * @param String $msg Message to be logged
     *
     **/
    protected function log($msg)
    {
        $matches = [];

        preg_match('/[^\\\\]+$/', static::class, $matches);

        if( $this->logUuid ) {
            $this->logger->info(
                $this->logUuid
                . " Manager: "
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg
            );
        } else {
            $this->logger->info(
                'Manager: '
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg);
        }
    }

    /**
     *   Function that returns the temp upload directory where uploaded documents
     *   are saved on disc before passing them on to Box API
     *
     **/
    public function getDocumentUploadDir() {

        return $this->config["kernel_root"] . '/' . $this->config["upload_temp_folder"] . '/';
    }

}
