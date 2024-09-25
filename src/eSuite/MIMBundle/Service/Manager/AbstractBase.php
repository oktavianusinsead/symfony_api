<?php

namespace esuite\MIMBundle\Service\Manager;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\NoopWordInflector;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Entity\UserToken;
use Psr\Log\LoggerInterface;
use esuite\MIMBundle\Service\edotNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\SerializerInterface;
use esuite\MIMBundle\Service\edotCourseBackup as Backup;
use esuite\MIMBundle\Service\Redis\Base as RedisBase;
use Exception;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Exception\BoxGenericException;

use esuite\MIMBundle\Entity\Base as BaseEntity;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use Smalot\PdfParser\Parser;

Use esuite\MIMBundle\MIMBundle;
/***
 * Class AbstractBase
 * @package esuite\MIMBundle\Service\Manager\
 *
 *
 *
 */
abstract class AbstractBase
{
    protected $logger;
    protected $notify;
    protected $entityManager;
    protected $validator;
    protected $serializer;
    protected $backup;
    protected $userProfileManager;
    protected $env;

    protected $logUuid;

    /**
     *  @var string
     */
    protected static $SCOPE_STUDENT = "mimstudent";

    /**
     * @var array that contains the list of file extensions that can be uploaded as Session Content & in Subtasks
     */
    protected static $UPLOAD_FILE_TYPES = ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx', 'mp4', 'm4v', 'm4a', 'm4b', 'm4p', 'm4r'];

    public static $FACULTY_ROLE       = 7;
    public static $MIM_ADMIN_ROLES    = ['Staff', 'Faculty', 'esuite Contractor'];
    public static $MIM_ADMIN_ROLES_ID = [9, 7, 98];

    public function __construct(protected RedisBase $redisMainObj, LoggerInterface $logger, edotNotify $notify, EntityManager $em, ValidatorInterface $validator, SerializerInterface $serializer, Backup $backup)
    {
        $this->logger               = $logger;
        $this->notify               = $notify;
        $this->entityManager        = $em;
        $this->validator            = $validator;
        $this->serializer           = $serializer;
        $this->backup               = $backup;
    }

    public function setEnvironment($env){
        $this->env = $env;
    }

    public function setLogger($logger){
        $this->logger = $logger;
    }

    public function getLogger(){
        return $this->logger;
    }

    public function setNotify($notify){
        $this->notify = $notify;
    }

    public function getNotify(){
        return $this->notify;
    }

    public function setEntityManager($em){
        $this->entityManager = $em;
    }

    public function getEntityManager(){
        return $this->entityManager;
    }

    public function setJMSSerializer($serializer){
        $this->serializer = $serializer;
    }

    public function getJMSSerializer(){
        return $this->serializer;
    }

    public function setValidator($validator){
        $this->validator = $validator;
    }

    public function getValidator(){
        return $this->validator;
    }

    public function setBackup($backup){
        $this->backup = $backup;
    }

    public function getBackup(){
        return $this->backup;
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
     * Function to send notifications if needed
     * @param   String      $logUuid        loguuid for the user
     * @param   Boolean     $toNotify       flag to determine if notification is needed
     * @param   Course      $course         course object where the notification is needed
     * @param   String      $type           type of notification to be sent
     */
    protected function notify($logUuid, $toNotify, Course $course, $type) {
        if ($toNotify) {
            $this->notify->setLogUuidWithString($logUuid);
            $this->notify->message($course, $type);
        }
    }

    /**
     * Function to remember the logUuid
     * @param String $logUuid for the user
     */
    public function setLogUuid($logUuid) {
        $this->logUuid = $logUuid;
    }

    /**
     * Function to remember the logUuid
     * @param Request $request request sent to the manager
     */
    public function setLogUuidFromRequest(Request $request) {
        $headers = $request->headers;

        $bearerHeader = "Bearer ";

        $authHeader = $headers->get('Authorization');

        $authPrefix = ($authHeader ? substr( $authHeader, 0, strlen($bearerHeader) ) : '');
        $authToken = ($authHeader ? substr( $authHeader, strlen($bearerHeader) ) : '');

        if( strcmp($authPrefix,$bearerHeader) == 0 ) {
            $this->setLogUuid("[" . substr($authToken,0,8) ."..." . substr($authToken,-8) ."]");
        }
    }

    /**
     * Function to remember the logUuid from service
     */
    public function setLogUuIdFromService(RequestStack $requestStack){
        if ($requestStack->getCurrentRequest())
            $this->setLogUuidFromRequest($requestStack->getCurrentRequest());
    }

    public function serializeValidationError(ConstraintViolationListInterface $validationErrors)
    {
        $curatedErrorMsg = [];
        $totalErrors = $validationErrors->count();

        for ($i=0; $i<$totalErrors; $i++) {
            $error = $validationErrors->get($i);
            $curatedErrorMsg[ $error->getPropertyPath() ] = [$error->getMessage()];
        }
        return $curatedErrorMsg;
    }

    public function getCurrentUserScope(Request $request)
    {
        $session = $request->getSession();
        return $session->get('scope');
    }

    public function getCurrentUserPsoftId(Request $request)
    {
        $session = $request->getSession();
        return $session->get('user_psoftid');
    }

    public function getCurrentUserId(Request $request)
    {
        $session = $request->getSession();
        return $session->get('user_id');
    }

    public function getCurrentUser(Request $request)
    {
        // Get Authorization Header (strip "Bearer " from beginning")
        $token = substr( (string) $request->headers->get('Authorization'), 7 );
        /** @var UserToken $user */
        $userToken = $this->entityManager
            ->getRepository(UserToken::class)
            ->findOneBy( [ 'oauth_access_token' => $token ] );
        if ( !$userToken ) {
            throw new ResourceNotFoundException(['error'=>['code'=> "404", "message"=>"Invalid Token"]]);
        }
        return $userToken->getUser();
    }

    /**
     * Function to get current user
     *
     * @param Request        $request         request object
     *
     * @throws ResourceNotFoundException
     *
     * @return User
     */
    public function getCurrentUserObj(Request $request)
    {
        $session = $request->getSession();

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['id' => $session->get('user_id')]);

        if(!$user) {
            $this->log('User not found');
            throw new ResourceNotFoundException('User not found');
        }

        return $user;
    }

    /**
     * Creates a record in DB for any Entity
     *
     * @param $entityName
     *
     * @return Response
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function createRecord($entityName, BaseEntity $data)
    {
        $keyname = strtolower((string) $entityName);

        // Validate Data
        $validationErrors = $this->validator->validate($data);

        if (count($validationErrors) > 0) {
            throw new InvalidResourceException($this->serializeValidationError($validationErrors));
        }

        $em = $this->entityManager;
        $em->persist($data);
        $em->flush();

        //Serialize Doctrine entity object
        $serializedData = $this->serializer->serialize([$keyname => $data], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(201);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Updates a record in DB for any Entity
     *
     * @param $entityName
     * @param null $keyname
     *
     * @return Response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function updateRecord($entityName, BaseEntity $data, $keyname = null)
    {
        if(is_null($keyname)) {
            $keyname = strtolower((string) $entityName);
        }

        $this->log('VALIDATING BEFORE UPDATING');
        $validationResult = $this->validator->validate($data);
        if(count($validationResult) > 0) {
            $errors = [];

            foreach($validationResult as $e) {

                $errors['errors'][$e->getPropertyPath()] = [$e->getMessage()];
            }

            $response = new Response(json_encode($errors));
            $response->setStatusCode(422);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $em = $this->entityManager;
        $em->persist($data);
        $em->flush();

        $serializedData = $this->serializer->serialize([$keyname => $data], 'json');

        $response = new Response($serializedData);
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Lists all records for any Entity
     *
     * @param String $entityName   Name of the Entity
     * @return array        List of all records in DB
     */
    protected function listAllRecords($entityName) {
        $data = $this->entityManager
            ->getRepository('esuite\MIMBundle\Entity\\'.$entityName)
            ->findAll();

        return [strtolower($entityName).'s' => $data];
    }

    protected function validateObject($object)
    {
        $validationErrors = $this->validator->validate($object);
        if (count($validationErrors) > 0) {
            throw new InvalidResourceException($this->serializeValidationError($validationErrors));
        }
    }

    /**
     * Validate the request does not try to modify the parent relationship
     *
     * @param String $type The name of the parent relationship
     * @param Request $request The request object passed to the REST handler
     *
     * @throws InvalidResourceException if the request updates the relationship
     */
    protected function validateRelationshipUpdate($type, Request $request)
    {
        if ($request->get($type)) {
            throw new InvalidResourceException([$type => [$type . ' cannot be modified']]);
        }
    }

    protected function loadDataFromRequest( Request $request, $list ) {
        $data = [];

        foreach ( explode(",",(string) $list) as $item ) {
            $data[ $item ] = $request->get( $item );
        }

        return $data;
    }

    /**
     *  Function to get User's profile data
     *
     * @param $peoplesoftId
     *
     * @return array|null
     *
     */
    protected function getUserProfileData(Request $request, $peoplesoftId)
    {
        return  $this->userProfileManager->getUserProfile($request,$peoplesoftId);
    }

    public function setUserProfile(UserProfileManager $userProfileManager){
        $this->userProfileManager = $userProfileManager;
    }

    private function getCleanNationality($nationality) {
        $nationalityArr = explode(' - ',(string) $nationality);
        if( count($nationalityArr) >= 2 ) {
            if( trim($nationalityArr[0]) != "" ) {
                $nationality = trim($nationalityArr[0]);
            }
        }

        return $nationality;
    }

    /**
     * Function to cleanup the Folder and File Names
     *
     * @param String        $name       Folder name or File name to be cleaned
     *
     * @return String;
     */
    protected function cleanName( $name ) {
        $name = preg_replace('/[^a-zA-Z0-9- ]+/', '_', $name);

        return $name;
    }

    /**
     * Handler function to list assigned users to a Programme
     *
     * @param bool $includeStudent
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    public function checkReadWriteAccessToProgramme(Request $request, Programme $programme, $includeStudent=false) {
        $scope = $this->getCurrentUserScope($request);
        $user = $this->getCurrentUserObj($request);

        if( $scope != "edotsuper" ) {
            //check if user has access to the programme
            $programme->setRequestorId($user->getId());
            $programme->setRequestorScope($scope);
            $programme->setIncludeHidden(true);
            $programme->setForStaff(true);

            if( $includeStudent ) {
                $programme->setForParticipant(true);
            }

            if(!$programme->checkIfMy()) {
                $this->log('User' . $user->getId() . " does not have RW access to Prog" . $programme->getId());
                throw new InvalidResourceException(['Your access to this programme is read-only.']);
            }
        }
    }

    /**
     * Handler function to list assigned users to a Programme
     *
     * @param $courseId
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    public function checkReadWriteAccess(Request $request, $courseId) {
        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(["id"=>$courseId]);

        if ($course) $this->checkReadWriteAccessToProgramme($request,$course->getProgramme());
    }

    /**
     * Finds a record by its id in DB for any Entity
     *
     * @param    String     $entityName         Name of the Entity
     * @param    integer    $entityId           id of the entity to be found
     *
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     *
     * @return object
     */
    public function findById($entityName, $entityId)
    {
        try {
            $data = $this->entityManager
                ->getRepository('esuite\MIMBundle\Entity\\'.$entityName)
                ->find($entityId);
        } catch (Exception) {
            $inflector = new Inflector(new NoopWordInflector(), new NoopWordInflector());
            $modelName = $inflector->tableize($entityName) . '_id';

            throw new InvalidResourceException([$modelName => [$entityName . ' cannot be blank']]);
        }

        if (!$data) {
            $this->log($entityName. ' with id:'.$entityId.' not found');
            throw new ResourceNotFoundException($entityName. ' with id:'.$entityId.' not found');
        }
        return $data;
    }

    protected function isTesting()
    {
        return $this->notify->getEnvironment() == 'test';
    }

    /**
     * Generate a unique id
     *
     * @param $prefix
     * @return string
     */
    public function uniqueId($prefix)
    {
        return $prefix . '_' . date('Hisu') . random_int(100, 999999);
    }

    /**
     * Get the corresponding value in Redis from key
     *
     * @param $key
     * @return bool|mixed|string
     */
    public function getRedisValueForKey($key)
    {
        $this->redisConfig();
        return $this->redis->get($key);
    }

    /**
     * Get the corresponding value in Redis from key
     *
     * @param $key
     * @param $value
     * @return bool
     */
    public function setRedisValueForKey($key, $value)
    {
        $this->redisConfig();
        return $this->redis->set($key, $value);
    }

    /**
     * Configure the redis if set or not
     */
    private function redisConfig()
    {
        if (!$this->redis) {
            $env = $this->env;
            $redisHost = $this->redisMainObj->getRedisHost();
            $redisPort = $this->redisMainObj->getRedisPort();

            $this->redis = new \Redis();
            $this->redis->connect($redisHost, $redisPort);
            $this->redis->setOption(\Redis::OPT_PREFIX, "mim:$env:");
        }
    }
}
