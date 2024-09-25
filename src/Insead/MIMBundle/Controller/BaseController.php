<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use DateTime;
use Insead\MIMBundle\Entity\UserToken;
use Insead\MIMBundle\Service\Redis\Saml;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Exception\BoxGenericException;
use Insead\MIMBundle\Exception\InvalidResourceException;

use FOS\RestBundle\Controller\AbstractFOSRestController;

use Doctrine\Inflector\Inflector;

use Insead\MIMBundle\Entity\Base;
use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseController extends AbstractFOSRestController
{
    protected $redis;
    protected $logUuid;
    private readonly ValidatorInterface $validator;
    private readonly SerializerInterface $serializer;
    public function __construct(public LoggerInterface $logger,
                                public ManagerRegistry $doctrine,
                                public ParameterBagInterface $baseParameterBag,
                                public ManagerBase $base) {

        $this->validator = Validation::createValidator();
        $this->serializer = SerializerBuilder::create()->build();
    }

    /**
     * @var array that contains the list of file extensions that can be uploaded as Session Content & in Subtasks
     */
    protected static $UPLOAD_FILE_TYPES = ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx', 'mp4', 'm4v', 'm4a', 'm4b', 'm4p', 'm4r'];

    /**
    *   Function that returns the temp upload directory where uploaded documents
    *   are saved on disc before passing them on to Box API
    *
    **/
    protected function getDocumentUploadDir()
    {
        // real path returns false in some instances, so this has been commented as it should not be necessary
        return $this->getParameter('kernel.project_dir') . '/' . $this->baseParameterBag->get('upload_temp_folder') . '/';
    }

    protected function getUserProfilePicturePath()
    {
        return $this->getParameter('kernel.project_dir'). '/' . $this->baseParameterBag->get('user_profile_pictures_path');
    }


    protected function getRedisValueForKey($key)
    {
        return $this->base->getRedisValueForKey( $key );
    }

    protected function setRedisValueForKey($key, $value)
    {
        return $this->base->setRedisValueForKey($key, $value);
    }

    /**
     *   Function that returns a constructed json error message for Rest API endpoints
     *
     * @param $errorMsg
     * @return array
     */
    protected function getRestErrorResponse($errorMsg)
    {
        return ["error" => $errorMsg];
    }

    /**
     *   Function that logs a message, prefixing the Class and function name to help debug
     *
     * @param $msg
     */
    protected function log($msg)
    {
        $matches = [];

        preg_match('/[^\\\\]+$/', static::class, $matches);

        if( $this->logUuid ) {
            $this->logger->info(
                $this->logUuid
                . " "
                . $matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg
            );
        } else {
            $this->logger->info($matches[0]
                . ":"
                . debug_backtrace()[1]['function']
                . " - "
                . $msg);
        }
    }

    protected function getCurrentUserScope(Request $request)
    {
        return $this->base->getCurrentUserScope($request);
    }

    protected function getCurrentUserPsoftId(Request $request)
    {
        return $this->base->getCurrentUserPsoftId($request);
    }

    protected function getCurrentUserId(Request $request)
    {
        return $this->base->getCurrentUserId($request);
    }

    /**
     * @throws ResourceNotFoundException
     */
    protected function getCurrentUserObj(Request $request)
    {
        return $this->base->getCurrentUserObj($request);
    }

    /**
     * @throws ResourceNotFoundException
     */
    protected function getCurrentUser(Request $request)
    {
        return $this->base->getCurrentUser($request);
    }


    /**
     * Finds a record by its id in DB for any Entity
     *
     * @param    String     $entityName         Name of the Entity
     * @param    integer    $entityId           id of the entity to be found
     *
     * @return object
     */
    protected function findById($entityName, $entityId)
    {
        return $this->base->findById($entityName, $entityId);
    }

    /**
     * Creates a record in DB for any Entity
     *
     * @param   String      $entityName     Name of the Entity
     * @param   Base        $data           Entity Object to be created
     *
     * @throws InvalidResourceException
     *
     * @return Response        created data object
     */
    protected function create($entityName, Base $data)
    {
        $keyname = strtolower($entityName);

        // Validate Data
        $validationErrors = $this->validator->validate($data);

        if (count($validationErrors) > 0) {
            throw new InvalidResourceException($this->serializeValidationError($validationErrors));
        }

        $em = $this->doctrine->getManager();
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
     * Serializes the validation errors
     *
     * @param   ConstraintViolationList  $validationErrors     List of Violations
     *
     * @return array()
     */
    private function serializeValidationError($validationErrors)
    {
        $curatedErrorMsg = [];
        $totalErrors = $validationErrors->count();

        for ($i=0; $i<$totalErrors; $i++) {
            $error = $validationErrors->get($i);
            $curatedErrorMsg[ $error->getPropertyPath() ] = [$error->getMessage()];
        }
        return $curatedErrorMsg;
    }

    /**
     * Updates a record in DB for any Entity
     *
     * @param   String  $entityName     Name of the Entity
     * @param   Base    $data           Entity Object to be updated
     * @param   String  $keyname        field/key name to be updated
     *
     * @return Response        Updated data object
     */
    protected function update($entityName, Base $data, $keyname = null)
    {
        if(is_null($keyname)) {
            $keyname = strtolower($entityName);
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
        $em = $this->doctrine->getManager();
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
    * @param String     $entityName     Name of the Entity
    * @return array        List of all records in DB
    */
    protected function listAll($entityName) {
        $data = $this->doctrine
                    ->getRepository('Insead\MIMBundle\Entity\\'.$entityName)
                    ->findAll();
        return [strtolower($entityName).'s' => $data];
    }

    /**
     * Deletes a record in DB for any Entity
     *
     * @param   String      $entityName   Name of the Entity
     * @param   integer     $entityId     id of the entity to be deleted
     *
     * @throws ResourceNotFoundException
     *
     * @return Response
     */
    protected function deleteById($entityName, $entityId) {
        $data = $this->doctrine
                      ->getRepository('Insead\MIMBundle\Entity\\'.$entityName)
                      ->find($entityId);
        if(!$data) {
            $this->log($entityName. ' with id:'.$entityId.' not found');
            throw new ResourceNotFoundException($entityName. ' with id:'.$entityId.' not found');
        } else {
            //Delete item
            $em = $this->doctrine->getManager();
            $em->remove($data);
            $em->flush();

            $response = new Response();
            $response->setStatusCode(204);
        }
        return $response;
    }

    protected function findBy($entityName, $criteria)
    {
        $data = $this->doctrine
                    ->getRepository('Insead\MIMBundle\Entity\\'.$entityName)
                    ->findBy($criteria);
        if(!$data) {
            $this->log($entityName . ' not found');
            throw new ResourceNotFoundException($entityName . ' not found');
        }
        return $data;
    }

    /**
     * Validate the request does not try to modify the parent relationship
     *
     * @param String $type The name of the parent relationship
     * @param Request $request The request object passed to the REST handler
     *
     * @throws InvalidResourceException if the request updates the relationship
     */
    protected function validateRelationshipUpdate($type, $request)
    {
        if ($request->get($type)) {
            throw new InvalidResourceException([$type => [$type . ' cannot be modified']]);
        }
    }

    protected function addAuthorizationHeader($token, $options)
    {
        if( ! isset($options['headers']))
        {
            $options['headers'] = [];
        }

        $options['headers']['Authorization'] = 'Bearer ' . $token;

        return $options;
    }

    protected function setLogUuid(Request $request) {
        $headers = $request->headers;

        $bearerHeader = "Bearer ";

        $authHeader = $headers->get('Authorization');

        $authPrefix = substr( (string) $authHeader, 0, strlen($bearerHeader) );
        $authToken = substr( (string) $authHeader, strlen($bearerHeader) );

        if( strcmp($authPrefix,$bearerHeader) == 0 ) {
            $this->logUuid = "[" . substr($authToken,0,8) ."..." . substr($authToken,-8) ."]";
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
     * Handler function to list assigned users to a Programme
     *
     * @param Request       $request        request object received
     * @param Programme     $programme      programme being processed
     * @param Boolean       $includeStudent flag to determine if we should allow access for students
     *
     */
    protected function checkReadWriteAccessToProgramme(Request $request, Programme $programme, $includeStudent=false) {
        $this->base->checkReadWriteAccessToProgramme( $request, $programme, $includeStudent );
    }

    /**
     * Handler function to list assigned users to a Programme
     *
     * @param Request   $request        request object received
     * @param int       $courseId       id of course being processed
     *
     * @throws InvalidResourceException
     */
    protected function checkReadWriteAccess(Request $request, $courseId) {
        $this->base->checkReadWriteAccess( $request, $courseId );
    }

    /**
     * Corrects URLs by encoding the substrings
     *
     * @param String $url full URL information
     *
     * @return String
     */
    protected function customUrlEncode($url)
    {
        $url = trim($url);

        if( is_null($url) || $url == '' ) return $url;

        //process the information if there are invalid characters in the URL
        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE ) {

            $newURLArr = [];

            $urlArr = explode("/", $url);

            foreach ($urlArr as $item) {
                //url encode each item but skip the protocol part
                if ($item == "http:" || $item == "https:") {
                    array_push($newURLArr, $item);
                } else {
                    array_push($newURLArr, rawurlencode($item));
                }
            }

            $url = implode("/", $newURLArr);
        }

        return $url;
    }
}
