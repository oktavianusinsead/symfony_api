<?php

namespace Insead\MIMBundle\Service\Manager;

use DateTime;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use http\Exception\InvalidArgumentException;
use Insead\MIMBundle\Entity\CourseSubscription;
use Insead\MIMBundle\Entity\Organization;
use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Entity\UserProfile;
use Insead\MIMBundle\Entity\UserProfileCache;
use Insead\MIMBundle\Entity\UserToken;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\PermissionDeniedException;
use Insead\MIMBundle\Service\AIPService;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\StudyNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use Insead\MIMBundle\Service\BoxManager\BoxManager;
use Insead\MIMBundle\Service\StudyCourseBackup as Backup;
use Insead\MIMBundle\Service\S3ObjectManager;
use Insead\MIMBundle\Service\Redis\Base as RedisMain;
use Insead\MIMBundle\Service\Manager\BarcoManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Insead\MIMBundle\Exception\ResourceNotFoundException;


class UserProfileManager extends Base
{
    protected $s3;
    protected $redisAuthToken;
    protected $huddleUserManager;
    protected $redisMain;
    protected $secret;
    protected $environment;
    protected $validator;
    protected $userManager;
    protected $loginManager;
    protected $organizationManager;
    protected $AIPService;
    protected BarcoManager $barcoManager;

    protected $uploadDir;
    protected $rootDir;
    protected $aip_enabled;

    private $preferred_phone_choices = ['HOME', 'BUSN', 'BMOB'];
    private $preferred_email_choices = ['HOME', 'BUSN'];

    public static $PREFERRED_EMAIL_ENUM = ['0' => 'HOME', '1' => 'BUSN'];
    public static $PREFERRED_PHONE_ENUM = ['0' => 'HOME', '1' => 'BUSN', '2' => 'BMOB'];
    public static $CONTACTS_FIELDS_PHONE = ['personal_phone' => '0', 'work_phone' => '1', 'cell_phone' => '2'];
    public static $NOT_REQUIRED_FIELDS = ['personal_phone_prefix' => '0', 'work_phone_prefix' => '1', 'cell_phone_prefix' => '2', 'bio' => ''];
    public static $CONTACTS_FIELDS_EMAIL = ['personal_email'=> '0', 'work_email'=> '1'];


    public function loadServiceManager(S3ObjectManager $s3,$config, RedisMain $redisMain, AuthToken $redisAuthToken, HuddleUserManager $huddleUserManager, UserManager $userManager, LoginManager $loginManager, OrganizationManager $organizationManager, AIPService $AIPService, BarcoManager $barcoManager)
    {
        $this->s3                  = $s3;
        $this->validator           = $this->getValidator();

        $this->huddleUserManager   = $huddleUserManager;
        $this->userManager         = $userManager;
        $this->loginManager        = $loginManager;
        $this->organizationManager = $organizationManager;
        $this->AIPService          = $AIPService;

        $this->redisMain           = $redisMain;
        $this->redisAuthToken      = $redisAuthToken;
        $this->secret              = $config["secret"];
        $this->environment         = $config["symfony_environment"];

        $this->rootDir             = $config["kernel_root"];
        $this->uploadDir           = $config["upload_temp_folder"];

        $aip_config        = $config['aip_config'];
        $this->aip_enabled = $aip_config["aip_enabled"];

        $this->barcoManager = $barcoManager;
    }


    /**
     * Handler function to get person avatar
     *
     * @param Request $request      Expects Header parameters
     * @param string  $peoplesoftId Peoplesoft Id
     *
     * @throws ResourceNotFoundException
     *
     * @return Response
     */
    public function getUserAvatar(Request $request, $peoplesoftId)
    {
        $obj = $this->getAvatarS3Obj($peoplesoftId);

        $response = new Response();
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$peoplesoftId.'.jpeg');
        $response->setContent($obj['Body']);

        return $response;
    }

    /**
     * Handler to Fetch User avatar from CDN
     *
     * @param $peoplesoftId
     * @return mixed
     * @throws ResourceNotFoundException
     */
    public function getAvatarS3Obj($peoplesoftId){
        $path = "myinsead/profile-images/" . $this->generateAvatarHash($peoplesoftId) . ".jpg";

        if (!$this->s3 ) {
            $this->log('S3 service is not available (getAvatarS3Obj)');
            throw new ResourceNotFoundException('User Picture not found');
        }

        if( !$this->s3->checkIfCdnItemExists($path) ) {
            $this->log('User Picture not found ('.$peoplesoftId.')');
            throw new ResourceNotFoundException('User Picture not found');
        }

        return $this->s3->getCdnItem($path);
    }

    /**
     * function to generate hash-identifier for profile avatar
     *
     * @param String $peoplesoftId peoplesoft id of the user
     *
     * @return String
     */
    private function generateAvatarHash($peoplesoftId)
    {
        return md5($peoplesoftId);
    }

    /**
     * Handler function to Update profile information  of user without PeopleSoft Id in the URL
     *
     * @param Request $request Expects header parameter
     *
     * @return array
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     *
     */
    public function updateUserProfile(Request $request)
    {

        /** @var User $user */
        $user               = $this->getCurrentUser($request);

        $userCacheCurrentData = $this->getUserFromCache($user->getPeoplesoftId());

        $contentType        = $request->headers->get('Content-Type');
        $errorMessages      = [];

        if ((preg_match('/multipart\/form-data/', (string) $contentType))) {
            $data = $this->addRequestParamToArray(json_decode((string) $request->get('data'), true));
        } else {
            $data = $this->addRequestParamToArray(json_decode($request->getContent(), true));
        }

        $this->log("Processing profile information update" );

        foreach (['first_name', 'last_name'] as $keyCleanupKey)
        if (array_key_exists($keyCleanupKey, $data)) {
            $keyNameCleanup = trim((string) $data[$keyCleanupKey]);
            if (strlen($keyNameCleanup)) {
                $cleanedKey = implode(" ", array_filter(explode(" ", $keyNameCleanup)));
                $data[$keyCleanupKey] = $cleanedKey;
            }
        }

        if (array_key_exists('job', $data)) {
            if (array_key_exists('title', $data['job'])) {
                $dataTitle = trim((string) $data['job']['title']);
                if (strlen($dataTitle)) {
                    $cleanedKey = implode(" ", array_filter(explode(" ", $dataTitle)));
                    $data['job']['title'] = $cleanedKey;
                }
            }
        }

        $collectionConstraint = $this->profileConstraintsValidation();

        $phoneExist = $this->userManager->contactPhoneExist($data);

        if (!$phoneExist) {
            unset($collectionConstraint->fields['preferred_phone']);
        } else {
            $data['preferred_phone'] = intval($data['preferred_phone']);
        }

        if (!$user->getAllowedToUpdate()) {
            unset($collectionConstraint->fields['preferred_email']);
        } else {
            $data['preferred_email'] = intval($data['preferred_email']);
        }

        foreach( ['personal_phone_prefix','work_phone_prefix','cell_phone_prefix'] as $countrycode ) {
            if( isset($data[$countrycode]) ) {
                $data[$countrycode] = str_replace('+','',$data[$countrycode]);
            }
        }

        $validatorErrors = $this->validator->validate($data, $collectionConstraint);

        //=========PROFESSIONAL=========
        //validate Job fields
        $mainJobCollectionConstraint = $this->jobConstraintsValidation();

        if (empty($data['hide_phone'])) {
            $data['hide_phone'] = 0;
        }
        if (empty($data['hide_email'])) {
            $data['hide_email'] = 0;
        }

        if (array_key_exists('job', $data)) {

            $is_new_job = false;

            // Make Title mandatory
            if (!array_key_exists('title', $data['job'])) {
                $data['job']['title'] = "";
            }

            // Make organisation mandatory
            if (!array_key_exists('organisation', $data['job'])) {
                $data['job']['organisation'] = "";
            }

            // Make address_line_1 mandatory
            if (!array_key_exists('address_line_1', $data['job'])) {
                $data['job']['address_line_1'] = "";
            }

            // Make country mandatory
            if (!array_key_exists('country', $data['job'])) {
                $data['job']['country'] = "";
            }

            // Make postal_code mandatory
            if (!array_key_exists('postal_code', $data['job'])) {
                $data['job']['postal_code'] = "";
            }

            // Make City mandatory
            if(!array_key_exists('city', $data['job'])) {
                $data['job']['city'] = "";
            }

            // Unset job id as this is just a random number and should not be validated
            if(array_key_exists('id', $data['job'])) {
                unset($data['job']['id']);
            }

            $existingJob = false;
            if ($userCacheCurrentData->getJobTitle()
                || $userCacheCurrentData->getOrganizationTitle()
                || $userCacheCurrentData->getCountryCode()
                || $userCacheCurrentData->getAddressLine1()
                || $userCacheCurrentData->getAddressLine2()
                || $userCacheCurrentData->getAddressLine3()
                || $userCacheCurrentData->getPostalCode()
                || $userCacheCurrentData->getCity()
            ) {
                $existingJob = true;
            }

            $isMandatory = $this->userManager->checkRequiredFields($data['job'], $existingJob);

            if ($this->aip_enabled !== 'YES' || ($this->aip_enabled === 'YES' && $isMandatory)) {
                $jobValidatorErrors = $this->validator->validate($data['job'], $mainJobCollectionConstraint);

                if ($this->aip_enabled === 'YES' && !$existingJob) {
                    $is_new_job = true;
                }

                $countryName = $this->userManager->getCountryTitle($data['job']['country']);
                $data['job']['job_country'] = $countryName;

                if (!array_key_exists('state', $data['job'])) {
                    $data['job']['state'] = '';
                }

                if ($is_new_job || (!empty($data['job']['job_action']) && $data['job']['job_action'] == "add")) {
                    $data['job']['is_new_work_exp'] = true;
                }
            }

            if (count($jobValidatorErrors) > 0) {
                foreach ($jobValidatorErrors as $error) {
                    $errorField = explode('.', (string) $error->getPropertyPath())[0];
                    $errorField = str_replace('[','',$errorField);
                    $errorField = str_replace(']','',$errorField);

                    $errorMessages[ $errorField ] = [$error->getMessage()];
                }
            }
        }

        if (count($validatorErrors) > 0) {
            foreach ($validatorErrors as $error) {
                $errorField = explode('.', (string) $error->getPropertyPath())[0];
                $errorField = str_replace('[','',$errorField);
                $errorField = str_replace(']','',$errorField);
                $errorMessages[ $errorField ] = [$error->getMessage()];
            }
        }

        //=========CONTACTS========

        // check for field dependencies
        $this->userManager->validateDependentFields($errorMessages, $data, 'personal_phone', 'personal_phone_prefix', 'Contact : A Personal Phone number and country code must be specified.');
        $this->userManager->validateDependentFields($errorMessages, $data, 'work_phone', 'work_phone_prefix', 'Contact : A Work Phone number and country code must be specified.');
        $this->userManager->validateDependentFields($errorMessages, $data, 'cell_phone', 'cell_phone_prefix', 'Contact : A Cell Phone number and country code must be specified.');

        //Remove validation from all other fields except for Job Title.
        if (!$user->getAllowedToUpdate()) {

            foreach ($errorMessages as $errorKey => $eval) {
                if ($errorKey != "title") {
                    unset($errorMessages[$errorKey]);
                }
            }
        }

        if (count($errorMessages) > 0) {
            $this->log("Error occurred during Profile Update: " . json_encode($errorMessages));
            throw new InvalidResourceException($errorMessages);
        }

        if( isset($data['bio']) ) {
            $cleanedBio = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $data["bio"]);
            $cleanedBio = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $cleanedBio);
            $cleanedBio = preg_replace('#<link(.*?)>(.*?)</link>#is', '', $cleanedBio);

            //removing inline js events
            $cleanedBio = preg_replace("/([ ]on[a-zA-Z0-9_-]{1,}=\".*\")|([ ]on[a-zA-Z0-9_-]{1,}='.*')|([ ]on[a-zA-Z0-9_-]{1,}=.*[.].*)/","", $cleanedBio);

            //removing inline js
            $cleanedBio = preg_replace("/([ ]href.*=\".*javascript:.*\")|([ ]href.*='.*javascript:.*')|([ ]href.*=.*javascript:.*)/i","", $cleanedBio);
            $data['bio'] = $cleanedBio;
        }

        $data['peoplesoft_id'] = $user->getPeoplesoftId();
        if ((preg_match('/multipart\/form-data/', (string) $contentType))) {
            $data['company'] = json_decode((string) $request->get('data'), true)['company'];
            $this->updateStudyUserProfile($request, $data);
            $this->uploadProfilePictureToS3($request);
        } else {
            $data['company'] = json_decode($request->getContent(), true)['company'];
            $this->updateStudyUserProfile($request, $data);
        }

        return $this->getUserProfile($request, $data['peoplesoft_id']);
    }

    private function profileRefreshOfProfileBook(Request $request, $psoftID){
        $authHeader         = $request->headers->get('Authorization');

        //submit a profile refresh for profile-book by uploading a json file
        /** @var UserToken $serviceToken */
        $serviceToken = $this->loginManager->generateServiceToken( $authHeader );
        if( $serviceToken ) {
            $this->log("Triggering a refresh of cached profile info for " . $psoftID);
            $person = ["peoplesoft_id" => $psoftID, "service_token" => $serviceToken->getOauthAccessToken()];

            $result = $this->s3->uploadToS3(
                "preprocessed-resources/raw-user-ids/" . $psoftID . ".json",
                json_encode($person),
                true
            );

            $this->log( "Cached Profile info result [" . $psoftID . "]:" . json_encode($result) );
        }
    }

    /**
     * Handler to updload Image to S3 Bucket
     *
     * @return array|false
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    private function uploadProfilePictureToS3(Request $request){

        /** @var User $user */
        $user           = $this->getCurrentUser($request);

        /** @var string $peopleSoftID */
        $peopleSoftID   = $user->getPeoplesoftId();

        $uploadedFile = $request->files->get('file');

        if ($uploadedFile !== null) {
            $filename = $uploadedFile->getClientOriginalName();
            $filetype = $uploadedFile->getClientMimeType();
            $allowedTypes = ["image/png", "image/jpeg"];

            if (!in_array($filetype, $allowedTypes) ) {
                $errorMessages[ 'picture_data' ] = ['Personal: Invalid file type. Accept only jpg and png.'];
                $this->log("Error occurred during Profile Update: " . json_encode($errorMessages));
                throw new InvalidResourceException($errorMessages);
            }

            $uploadedFile->move($this->getDocumentUploadDir(), $filename);
            $path     = $this->getDocumentUploadDir().$filename;

            $pic_limit = 8; //10MB
            $limitSizeToUpload = 8; //2MB
            $fileSizeReal = filesize($path);
            $fileSizeLimit = ($pic_limit *1024*1024);
            $fileSizeLimitToUpload = ($limitSizeToUpload *1024*1024);

            $this->log( "Orig File Size: $fileSizeReal -- Limit upload: $fileSizeLimitToUpload" );

            if ( $fileSizeReal > $fileSizeLimitToUpload ) {
                $fileSizeDiff = ($fileSizeReal-$fileSizeLimit);
                $errorMessages[ 'picture_data' ] = ['Personal: Your image file exceeds '. $pic_limit .'MB  by '.$this->userManager->formatBytes($fileSizeDiff).'. Please upload a lighter jpeg / png image or click on the \'Scissors\' to resize.'];

                $this->log("Error occurred during Profile Update: " . json_encode($errorMessages));
                throw new InvalidResourceException($errorMessages);
            } else {
                $this->imageResize($path);
            }

            return $this->s3->uploadImageToCDNBucket(
                "myinsead/profile-images/" . $this->generateAvatarHash($peopleSoftID) . ".jpg",
                $path,
                false
            );
        }

        return false;
    }

    /**
     * Handler function to upload an avatar by admin
     *
     * @param $peopleSoftID
     *
     * @return mixed
     * @throws InvalidResourceException
     */
    public function uploadAvatarByAdmin(Request $request, $peopleSoftID){
        $this->log(print_r($request->files,true));
        $uploadedFile = $request->files->get('file');

        if ($uploadedFile !== null) {
            $filename = $uploadedFile->getClientOriginalName();
            $uploadedFile->move($this->getDocumentUploadDir(), $filename);
            $path     = $this->getDocumentUploadDir().$filename;

            $pic_limit = 8; //8MB
            $limitSizeToUpload = 8; //2MB
            $fileSizeReal = filesize($uploadedFile);;
            $fileSizeLimit = ($pic_limit *1024*1024);
            $fileSizeLimitToUpload = ($limitSizeToUpload *1024*1024);

            if ( $fileSizeReal > $fileSizeLimitToUpload ) {
                $fileSizeDiff = ($fileSizeReal - $fileSizeLimit);

                if ($fileSizeDiff > 0) {
                    return false;
                }
            } else {
                $this->imageResize($path);
            }

            return $this->s3->uploadImageToCDNBucket(
                "myinsead/profile-images/" . $this->generateAvatarHash($peopleSoftID) . ".jpg",
                $path,
                false
            );
        } else {
            $this->log("Avatar is null");
        }

        return false;
    }

    /**
     * Handler function to update Bio by admin
     *
     * @param $peopleSoftID
     * @return bool
     */
    public function updateBioByAdmin(Request $request, $peopleSoftID){
        $bio = $request->get('bio');
        /** @var User $user */
        $user = $this->getUserEntity($peopleSoftID);
        if ($user){
            /** @var UserProfileCache $cacheProfile */
            $cacheProfile = $user->getCacheProfile();

            /** @var UserProfile $coreProfile */
            $coreProfile  = $user->getCoreProfile();

            if ($cacheProfile){
                $cacheProfile->setBio($bio);
            }

            if ($coreProfile){
                $coreProfile->setBio($bio);
            }

            try {
                $this->entityManager->persist($cacheProfile);
            } catch (ORMException) {
                $this->log("Unable to persists table Cache on Admin update Bio");
            }

            try {
                $this->entityManager->persist($coreProfile);
            } catch (ORMException) {
                $this->log("Unable to persists table Core on Admin update Bio");
            }

            try {
                $this->entityManager->flush();
            } catch (OptimisticLockException | ORMException) {
                $this->log("Unable to flush database on Admin update Bio");
            }

            $this->log("Bio has been updated by Study Admin");

            return true;

        } else {
            return false;
        }
    }

    /**
     * Handler function to update Temp Job Title by admin
     *
     * @param $peopleSoftID
     * @return bool
     */
    public function updateTempJobTitleByAdmin(Request $request, $peopleSoftID){
        $tempJobTitle = $request->get('prefJobTitle');
        /** @var User $user */
        $user = $this->getUserEntity($peopleSoftID);
        if ($user){

            /** @var UserProfile $coreProfile */
            $coreProfile  = $user->getCoreProfile();

            if ($coreProfile){
                $coreProfile->setPreferredJobTitle($tempJobTitle);
            }

            try {
                $this->entityManager->persist($coreProfile);
            } catch (ORMException) {
                $this->log("Unable to persists table Core on Admin update Temp Job Title");
            }

            try {
                $this->entityManager->flush();
            } catch (OptimisticLockException | ORMException) {
                $this->log("Unable to flush database on Admin update Bio");
            }

            $this->log("Preferred Job Title has been updated by Study Admin");

            return true;
        } else {
            return false;
        }
    }

    /**
     * Function to update Study User profile
     *
     * @param $studyPayLoad
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function updateStudyUserProfile(Request $request, $studyPayLoad)
    {
        //check if we have a user for this PSOFT ID
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy([
                "peoplesoft_id" => $studyPayLoad['peoplesoft_id']
            ]);

        if (!$user) {
            $this->log("User not yet added by admin. Not allowed to update.");
            throw new InvalidResourceException(["You are not allowed to update."]);
        }

        /** @var UserProfileCache $cacheUser */
        $cacheUser = $this->getUserFromCache($user->getPeoplesoftId());

        /** @var UserProfile $coreUser */
        $coreUser = $this->getUserFromCore($user->getPeoplesoftId());

        if (!$cacheUser) {
            $this->log("User not yet in cache table. Not allowed to update.");
            throw new InvalidResourceException(["You are not allowed to update."]);
        }

        if (!$coreUser) {
            $this->log("User not yet in core table. Not allowed to update.");
            throw new InvalidResourceException(["You are not allowed to update."]);
        }

        $constituent_types = ($coreUser->getConstituentTypes() ? array_map('intval',array_map('trim', explode(",",$coreUser->getConstituentTypes()))) : NULL);
        if (!$constituent_types){
            $this->log("Users constituent type is blank from database. Not allowed to update.");
            throw new InvalidResourceException(["The constituent type id blank. You are not allowed to update."]);
        }

        if (!empty($studyPayLoad['job']['is_new_work_exp']) && $studyPayLoad['job']['is_new_work_exp'] === true) {
            $cacheUser->setWorkExperienceStatus(true);
        }

        $attributesToUpdate = [];
        $attributesToUpdate[] = ['setUser' => $user];

        if ($user->getAllowedToUpdate()){
            $profile_setter = [
                [ 'key' => 'first_name',            'fn' => 'setFirstname' ],
                [ 'key' => 'last_name',             'fn' => 'setLastname' ],
                [ 'key' => 'bio',                   'fn' => 'setBio' ],

                [ 'key' => 'cell_phone',            'fn' => 'setCellPhone' ],
                [ 'key' => 'cell_phone_prefix',     'fn' => 'setCellPhonePrefix' ],
                [ 'key' => 'personal_phone',        'fn' => 'setPersonalPhone' ],
                [ 'key' => 'personal_phone_prefix', 'fn' => 'setPersonalPhonePrefix' ],
                [ 'key' => 'work_phone',            'fn' => 'setWorkPhone' ],
                [ 'key' => 'work_phone_prefix',     'fn' => 'setWorkPhonePrefix' ],
                [ 'key' => 'preferred_phone',       'fn' => 'setPreferredPhone' ],

                [ 'key' => 'work_email',            'fn' => 'setWorkEmail' ],
                [ 'key' => 'personal_email',        'fn' => 'setPersonalEmail' ],
                [ 'key' => 'preferred_email',       'fn' => 'setPreferredEmail' ],
                [ 'key' => 'hide_phone',       'fn' => 'setHidePhone' ],
                [ 'key' => 'hide_email',       'fn' => 'setHideEmail' ],
            ];

            $job_setter = [
                [ 'key' => 'organisation',   'fn' => 'setOrganizationTitle' ],
                [ 'key' => 'title',          'fn' => 'setJobTitle' ],
                [ 'key' => 'address_line_1', 'fn' => 'setAddressLine1' ],
                [ 'key' => 'address_line_2', 'fn' => 'setAddressLine2' ],
                [ 'key' => 'address_line_3', 'fn' => 'setAddressLine3' ],
                [ 'key' => 'state',          'fn' => 'setState' ],
                [ 'key' => 'postal_code',    'fn' => 'setPostalCode' ],
                [ 'key' => 'country',        'fn' => 'setCountryCode' ],
                [ 'key' => 'city',           'fn' => 'setCity' ],
                [ 'key' => 'job_country',    'fn' => 'setCountry' ]
            ];
        } else {
            $profile_setter = [
                [ 'key' => 'bio', 'fn' => 'setBio' ],
                [ 'key' => 'hide_phone', 'fn' => 'setHidePhone' ],
                [ 'key' => 'hide_email', 'fn' => 'setHideEmail' ],
            ];

            $job_setter = [
                [ 'key' => 'title', 'fn' => 'setJobTitle' ]
            ];

            $this->log("Users constituent type is: ".$coreUser->getConstituentTypes().". Only updating Photo, Bio, and Preferred Job Title");
        }

        foreach($profile_setter as $obj_setter) {
            $key    = $obj_setter['key'];
            $setter = $obj_setter['fn'];
            if (array_key_exists($key, $studyPayLoad)) {
                if (strlen((string) $studyPayLoad[$key])) {
                    array_push($attributesToUpdate, [$setter => $studyPayLoad[$key]]);
                } else {

                    if (array_key_exists($key, self::$CONTACTS_FIELDS_PHONE)) {

                        if ($studyPayLoad['preferred_phone'] != self::$CONTACTS_FIELDS_PHONE[ $key ]){
                            array_push($attributesToUpdate, [$setter => ""]);
                        }
                    }

                    if (array_key_exists($key, self::$CONTACTS_FIELDS_EMAIL)) {

                        if ($studyPayLoad['preferred_email'] != self::$CONTACTS_FIELDS_EMAIL[ $key ]){
                            array_push($attributesToUpdate, [$setter => ""]);
                        }
                    }

                    if (array_key_exists($key, self::$NOT_REQUIRED_FIELDS)) {
                        array_push($attributesToUpdate, [$setter => ""]);
                    }
                }
            }
        }

        $jobBlock = $studyPayLoad['job'];
        foreach($job_setter as $obj_setter) {
            $key    = $obj_setter['key'];
            $setter = $obj_setter['fn'];
            if (array_key_exists($key, $jobBlock)) {
                if (strlen((string) $jobBlock[$key])) {
                    array_push($attributesToUpdate, [$setter => $jobBlock[$key]]);
                } else {
                    array_push($attributesToUpdate, [$setter => ""]);
                }
            }
        }

        if (count($attributesToUpdate) > 0){
            foreach ($attributesToUpdate as $attributeValue){
                $setterName = key($attributeValue);
                switch ($setterName){
                    case "setOrganizationTitle":
                        $company_name = $attributeValue[$setterName];

                        /** @var array $org_qb */
                        $org_list = $this->entityManager
                            ->getRepository(Organization::class, 'o')
                            ->createQueryBuilder('o')
                            ->where($this->entityManager->createQueryBuilder()->expr()->like('o.title', ':title'))
                            ->setParameter('title',$company_name)
                            ->getQuery()
                            ->getResult();

                        if (count($org_list) > 0){

                            /** @var Organization $organization_obj */
                            $organization_obj = $org_list[0];
                            $companyName = $organization_obj->getTitle();
                            $companyID   = $organization_obj->getExtOrgId();

                            $cacheUser->$setterName($companyName);
                            $cacheUser->setOrganizationId($companyID);

                            $coreUser->$setterName($companyName);
                            $coreUser->setOrganizationId($companyID);

                        } else {

                            $cacheUser->$setterName($attributeValue[$setterName]);
                            $cacheUser->setOrganizationId(NULL);

                            $coreUser->$setterName($attributeValue[$setterName]);
                            $coreUser->setOrganizationId(NULL);
                        }
                        break;
                    case "setJobTitle":
                        if ($user->getAllowedToUpdate()) {
                            $cacheUser->$setterName($attributeValue[$setterName]);
                            $coreUser->$setterName($attributeValue[$setterName]);
                        }

                        $coreUser->setPreferredJobTitle($attributeValue[$setterName]);
                        break;
                    case "setHidePhone":
                        $coreUser->setHidePhone($attributeValue[$setterName]);
                        break;
                    case "setHideEmail":
                        $coreUser->setHideEmail($attributeValue[$setterName]);
                        break;
                    default:
                        $cacheUser->$setterName($attributeValue[$setterName]);
                        $coreUser->$setterName($attributeValue[$setterName]);
                }
            }

            $changeSet = [];
            if ($user->getAllowedToUpdate()) {
                $this->log("Getting changed set in profile data");
                $uow = $this->entityManager->getUnitOfWork();
                $uow->computeChangeSets();
                $changeSet = $uow->getEntityChangeSet($cacheUser);
            }

            try {
                $this->entityManager->persist($cacheUser);
            } catch (ORMException) {
                $this->log("Unable to persists cache user table");
            }

            try {
                $this->entityManager->persist($coreUser);
            } catch (ORMException) {
                $this->log("Unable to persists core user table");
            }

            try {
                $this->entityManager->flush();
            } catch (OptimisticLockException|ORMException) {
                $this->log("Unable to flush database");
            }

            if (count($changeSet) > 0) {
                $currentUpdatedFields = $cacheUser->getUpdatedFields();
                if (!$cacheUser->getProfileUpdated()) {
                    $currentUpdatedFields = "";
                }


                $currentUpdatedFieldsArray = (strlen($currentUpdatedFields) > 1 ? explode(",",$currentUpdatedFields) : [] );
                if (count($currentUpdatedFieldsArray) > 0) {
                    $newUpdatedFieldsArray = array_unique(array_merge($currentUpdatedFieldsArray, array_keys($changeSet)));
                } else {
                    $newUpdatedFieldsArray = array_keys($changeSet);
                }

                $blockUpdate = [
                    "name"          => ["firstname","lastname"],
                    "cellPhone"     => ["cell_phone","cell_phone_prefix"],
                    "personalPhone" => ["personal_phone","personal_phone_prefix"],
                    "workPhone"     => ["work_phone","work_phone_prefix"],
                    "personalEmail" => ["personal_email"],
                    "workEmail"     => ["work_email"],
                    "prefEmail"     => ["preferred_email"],
                    "prefPhone"     => ["preferred_phone"],
                    "work"          => ["job_title","organization_title","organization_id","is_main","is_new_work_exp","work_country"],
                    "address"       => ["is_new_work_exp","address_line_1","address_line_2","address_line_3","state","postal_code","country_code","city"]
                ];

                $tmpArrayFields = [];
                foreach ($blockUpdate as $keyBlock => $blockUpdateCategory){
                    foreach ($blockUpdateCategory as $updatedColumn){
                        if (in_array($updatedColumn, $newUpdatedFieldsArray)){
                            $tmpArrayFields = array_merge($tmpArrayFields,$newUpdatedFieldsArray, $blockUpdateCategory);
                            if ($keyBlock == "address"){
                                $tmpArrayFields = array_merge($tmpArrayFields,$newUpdatedFieldsArray, $blockUpdate['work']);
                            }
                            continue;
                        }
                    }
                }

                if ((in_array("personal_email", $tmpArrayFields) || in_array("work_email", $tmpArrayFields)) && !in_array("preferred_email", $tmpArrayFields)){
                    array_push($tmpArrayFields,"preferred_email");
                }

                if (in_array("preferred_email", $tmpArrayFields) && !(in_array("personal_email", $tmpArrayFields) || in_array("work_email", $tmpArrayFields))){
                    switch ($cacheUser->getPreferredEmail()) {
                        case 0:
                            array_push($tmpArrayFields,"personal_email");
                            break;
                        case 1:
                            array_push($tmpArrayFields,"work_email");
                            break;
                    }
                }

                if ((in_array("cell_phone", $tmpArrayFields) ||
                        in_array("personal_phone", $tmpArrayFields) ||
                        in_array("work_phone", $tmpArrayFields)) && !in_array("preferred_phone", $tmpArrayFields)){

                    array_push($tmpArrayFields,"preferred_phone");
                }

                if (in_array("preferred_phone", $tmpArrayFields) && !(in_array("cell_phone", $tmpArrayFields) ||
                        in_array("personal_phone", $tmpArrayFields) ||
                        in_array("work_phone", $tmpArrayFields))){

                    switch ($cacheUser->getPreferredPhone()) {
                        case 0:
                            $tmpArrayFields = array_merge($tmpArrayFields,["personal_phone","personal_phone_prefix"]);
                            break;
                        case 1:
                            $tmpArrayFields = array_merge($tmpArrayFields,["work_phone","work_phone_prefix"]);
                            break;
                        case 2:
                            $tmpArrayFields = array_merge($tmpArrayFields,["cell_phone","cell_phone_prefix"]);
                            break;
                    }
                }
                
                $newUpdatedFieldsArray = array_unique(array_merge($tmpArrayFields,$newUpdatedFieldsArray));
                $this->log("Setting updated fields for ".$user->getPeoplesoftId()." with (".print_r($newUpdatedFieldsArray,true).")");

                $indexFirstname = array_search('firstname', $newUpdatedFieldsArray);
                if($indexFirstname !== FALSE){
                    unset($newUpdatedFieldsArray[$indexFirstname]);
                }

                $indexLastname = array_search('lastname', $newUpdatedFieldsArray);
                if($indexLastname !== FALSE){
                    unset($newUpdatedFieldsArray[$indexLastname]);
                }

                $cacheUser->setUpdatedFields(implode(",",$newUpdatedFieldsArray));
                $cacheUser->setProfileUpdated(true);
                $cacheUser->setLastUserDateUpdated(new \DateTime());
                try {
                    $this->entityManager->persist($cacheUser);
                    $this->entityManager->flush($cacheUser);
                } catch (ORMException) {
                    $this->log("Unable to persists cache user table");
                }
            }

            $this->profileRefreshOfProfileBook($request, $user->getPeoplesoftId());
            $this->huddleUserManager->prepareVanillaUserInfo($this->formatUserProfileToStudy($cacheUser));
        }
    }

    /**
     * Handler function to Retrieve Full profile information of user with Peoplesoft Id in the URL
     *
     * @param $peoplesoftId
     * @param bool $isCurated
     * @param bool $bypassCache
     * @return array
     */
    public function getUserProfile(Request $request, $peoplesoftId, $isCurated = true, $bypassCache = false)
    {

        /** @var UserProfile $coreUser */
        $coreUser = $this->getUserFromCore($peoplesoftId);
        if ($coreUser) {
            $this->log("PEOPLESOFT ID:: " . $peoplesoftId . " has been fetched from Core Profile");

            if ($coreUser->getConstituentTypes()) {
                return ['profile' => $this->formatUserProfileToStudy($coreUser)];
            }
        } else {
            /** @var UserProfileCache $cacheUser */
            $cacheUser = $this->getUserFromCache($peoplesoftId);
            if ($cacheUser) {
                $this->log("PEOPLESOFT ID:: " . $peoplesoftId . " has been fetched from Cache Profile");
                if ($cacheUser->getConstituentTypes()) {
                    return ['profile' => $this->formatUserProfileToStudy($cacheUser)];
                }
            }
        }

        try {
            $body = $this->AIPService->getUserApi($peoplesoftId);
            $this->saveESBWithPayload($body);
        } catch (OptimisticLockException $e) {
            $this->log("OptimisticLockException Error: ".$e->getMessage());
        } catch (ORMException $e) {
            $this->log("ORMException Error: ".$e->getMessage());
        } catch (InvalidResourceException $e) {
            $this->log("InvalidResourceException Error: ".$e->getMessage());
        } catch (\Exception $e){
            $this->log("General Exception error Error: ".$e->getMessage());
        }

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy([
                "peoplesoft_id" => $peoplesoftId
            ]);

        if ($user){
            if ($user->getCacheProfile()){
                /** @var UserProfileCache $cacheUser */
                $cacheUser = $user->getCacheProfile();
                if ($cacheUser->getConstituentTypes()) {
                    return ['profile' => $this->formatUserProfileToStudy($cacheUser)];
                } else {
                    $scope = $this->getCurrentUserScope($request);
                    if ($scope == "studyadmin" || $scope == "studysuper") {
                        return ['profile' => $this->formatUserProfileToStudy($cacheUser)];
                    }
                }
            }
        }

    }

    /**
     * Handler function to Retrieve Basic profile information of users with Peoplesoft Ids in the URL
     *
     * @param $psoftIds
     * @return array
     *
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     * @throws PermissionDeniedException
     * @throws ORMException|InvalidResourceException
     */
    public function getBasicProfiles(Request $request, $psoftIds)
    {

        $users = [];
        $invalidUsers = [];
        $ids = $psoftIds;

        if ($ids) {
            $batchIds = array_chunk( $ids, 50 );

            foreach( $batchIds as $batch ) {
                $newBatchID = [];

                // check if profile exists in cache
                /** @var string $psoftID */
                foreach ($batch as $psoftID) {
                    $psoftIDFound = false;

                    /** @var UserProfile $coreUser */
                    $coreUser = $this->getUserFromCore($psoftID);
                    if ($coreUser) {
                        $this->log("PEOPLESOFT ID:: " . $psoftID . " has been fetched from Core Profile");
                        $users[] = $this->formatUserProfileToStudy($coreUser);
                        $psoftIDFound = true;
                    } else {
                        /** @var UserProfileCache $cacheUser */
                        $cacheUser = $this->getUserFromCache($psoftID);
                        if ($cacheUser) {
                            $this->log("PEOPLESOFT ID:: " . $psoftID . " has been fetched from Cache Profile");
                            $users[] = $this->formatUserProfileToStudy($cacheUser);
                            $psoftIDFound = true;
                        }
                    }

                    if (!$psoftIDFound) {
                        try {
                            $body = $this->AIPService->getUserApi($psoftID);
                            $this->saveESBWithPayload($body);

                            /** @var User $user */
                            $user = $this->entityManager->getRepository(User::class)
                                ->findOneBy([
                                    "peoplesoft_id" => $psoftID
                                ]);

                            if ($user) {
                                if ($user->getCacheProfile()) {
                                    $cacheUser = $user->getCacheProfile();
                                    if ($cacheUser->getConstituentTypes()) {
                                        $users[] = $this->formatUserProfileToStudy($cacheUser);
                                    }
                                } else {
                                    $newBatchID[] = $psoftID;
                                }
                            }
                        } catch(\Exception $e) {
                            $this->log("Error: ".$e->getMessage());
                        }
                    }

                }

                $this->log("PEOPLESOFT IDs:: " . implode(",", $newBatchID)." is available from core/cache");
            }

            $this->entityManager->flush();

            //loop through all item and see if there are missing ones
            //fill the missing items with $invalidUser object
            foreach( $ids as $peoplesoftId ) {
                $isFound = false;
                foreach( $users as $user ) {
                    if( strcmp( (string) $user['peoplesoft_id'], (string) $peoplesoftId) == 0 ) {
                        $isFound = true;
                    }
                }

                if( !$isFound ) {
                    $invalidUsers[] = ['peoplesoft_id' => $peoplesoftId];
                }
            }

        }

        $scope = $this->getCurrentUserScope($request);
        if ($scope === 'studystudent') {
            // Check if all users have a common programme
            $validPeopleSoftIDs = array_column($users, 'peoplesoft_id');

            $usersObjectList = $this->entityManager
                ->getRepository(User::class)
                ->findBy(['peoplesoft_id' => $validPeopleSoftIDs]);

            $listOfProgrammeID = [];
            /** @var User $userObject */
            foreach ($usersObjectList as $userObject) {
                /** @var array $subscriptions */
                $subscriptions = $this->entityManager
                    ->getRepository(CourseSubscription::class)
                    ->findBy(['user' => $userObject]);

                if (!$subscriptions) {
                    $this->log('Student role fetching peoplesoft id: '.$userObject->getPeoplesoftId().' with no active enrollment');
                    throw new PermissionDeniedException('You are not authorized to access this API Endpoint.');
                }

                $listOfProgrammeIDPerStudent = [];
                /** @var CourseSubscription $subscriptionObject */
                foreach ($subscriptions as $subscriptionObject) {
                    $listOfProgrammeIDPerStudent[] = $subscriptionObject->getProgramme()->getId();
                }

                $listOfProgrammeID[] = $listOfProgrammeIDPerStudent;
            }

            $intersectListOfProgrammeIDs = call_user_func_array('array_intersect',$listOfProgrammeID);
            if (count($intersectListOfProgrammeIDs) < 1) {
                throw new PermissionDeniedException('You are not authorized to access this API Endpoint.');
            }
        }

        $users = array_merge($users, $invalidUsers);

        return ['profiles' => $users];

    }

    public function encryptProfile($string) {
        return openssl_encrypt( $string, "aes256", $this->secret );
    }

    public function decryptProfile($encryptedString) {
        return openssl_decrypt( $encryptedString, "aes256", $this->secret );
    }

    /**
     * Get User from Core Table
     * @param $psoftID
     *
     * @return object|null
     */
    private function getUserFromCore($psoftID){
        /** @var User $user */
        $user = $this->getUserEntity($psoftID);

        if ($user)
            return $user->getCoreProfile();
        else
            return null;
    }

    /**
     * Get User from User Cache Table
     * @param $psoftID
     *
     * @return object|null
     */
    private function getUserFromCache($psoftID){
        /** @var User $user */
        $user = $this->getUserEntity($psoftID);

        if ($user)
            return $user->getCacheProfile();
        else
            return null;
    }

    /**
     * Get User Entity
     *
     * @param $psoftID
     *
     * @return object|null
     */
    private function getUserEntity($psoftID){
        return $this->entityManager->getRepository(User::class)
                ->findOneBy(["peoplesoft_id" => $psoftID]);
    }

    /**
     * Format to Study@INSEAD
     * @param UserProfile|UserProfileCache $userProfile
     * @return array
     */
    private function formatUserProfileToStudy($userProfile){
        $faculty = false;
        $constituent_type_array = explode(',',$userProfile->getConstituentTypes());
        foreach ($constituent_type_array as $cType) {
            if (trim($cType) == 7) { // 7 = Faculty
                $faculty = true;
                break;
            }
        }

        /** @var User $user */
        $user = $userProfile->getUser();

        $profileData = ['peoplesoft_id' => $user->getPeoplesoftId(), 'first_name'            => $userProfile->getFirstname(), 'last_name'             => $userProfile->getLastname(), 'email'                 => ($userProfile->getUpnEmail() ?? ""), 'nationality'           => ($userProfile->getNationality() ?? ""), 'job_title'             => (method_exists($userProfile, 'getPreferredJobTitle') ? $userProfile->getPreferredJobTitle() : $userProfile->getJobTitle()), 'job_country'           => ($userProfile->getCountry() ?? ""), 'company'               => ($userProfile->getOrganizationTitle() ?? ""), 'bio'                   => ($userProfile->getBio() ?? ""), 'cell_phone'            => ($userProfile->getCellPhone() ?? ""), 'cell_phone_prefix'     => ($userProfile->getCellPhonePrefix() ?? ""), 'work_phone'            => ($userProfile->getWorkPhone() ?? ""), 'work_phone_prefix'     => ($userProfile->getWorkPhonePrefix() ?? ""), 'personal_phone'        => ($userProfile->getPersonalPhone() ?? ""), 'personal_phone_prefix' => ($userProfile->getPersonalPhonePrefix() ?? ""), 'preferred_phone'       => ($userProfile->getPreferredPhone() ?? ""), 'work_email'            => ($userProfile->getWorkEmail() ?? ""), 'personal_email'        => ($userProfile->getPersonalEmail() ?? ""), 'preferred_email'       => ($userProfile->getPreferredEmail() ?? ""), 'is_faculty'            => $faculty, 'vanilla_user_id'       => $user->getVanillaUserId(), 'is_allowed_to_update'  => ($this->aip_enabled === 'YES' ? $user->getAllowedToUpdate() : true), 'hide_phone'            => (method_exists($userProfile, 'getHidePhone') ? $userProfile->getHidePhone() : 0), 'hide_email'            => (method_exists($userProfile, 'getHideEmail') ? $userProfile->getHideEmail() : 0)];

        // User Job
        $profileData['job'] = ['title'          =>  (method_exists($userProfile, 'getPreferredJobTitle') ? $userProfile->getPreferredJobTitle() : $userProfile->getJobTitle()), 'description'    => ($userProfile->getBio() ?: ""), 'start_date'     => $userProfile->getJobStartDate(), 'end_date'       => $userProfile->getJobEndDate(), 'organisation'   => ($userProfile->getOrganizationTitle() ?: ""), 'industry'       => ($userProfile->getIndustry() ?: ""), 'address_line_1' => ($userProfile->getAddressLine1() ?: ""), 'address_line_2' => ($userProfile->getAddressLine2() ?: ""), 'address_line_3' => ($userProfile->getAddressLine3() ?: ""), 'state'          => ($userProfile->getState() ?: ""), 'postal_code'    => ($userProfile->getPostalCode() ?: ""), 'country'        => ($userProfile->getCountryCode() ?: ""), 'city'           => ($userProfile->getCity() ?: ""), 'id'             => $user->getPeoplesoftId()];

        $profileData['new_format'] = "Yes";

        return $profileData;
    }

    /**
     * Handler for getting the list of updated profile.
     * This is used by ESB to fetch updated profiles
     *
     * @return array
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function profilesUpdated(Request $request){
        $from      = $request->get('from');
        $to        = $request->get('to');
        $maxResult = $request->get('page_size');
        $pageNum   = $request->get('page');

        $ESBFormattedUpdatedUsers = [];

        $numberOfPages = 0;
        $updatedUsers  = [];
        if ($from) {
            if (!$to){
                throw new InvalidResourceException('Missing To parameter');
            }

            if (strlen((string) $from) < 1 || strlen((string) $to) < 1){
                throw new InvalidResourceException('From and To should not be empty');
            }

            if (!$maxResult || strlen((string) $maxResult) < 1 || !is_numeric($maxResult)){
                throw new InvalidResourceException('Limit should not be empty or it should be numeric');
            }

            if (!$pageNum || strlen((string) $pageNum) < 1 || !is_numeric($pageNum)){
                $pageNum = 1;
            }

            $dateFormat = 'Y-m-d H:i:s';
            try {
                $fromDate = new \DateTime($from);
            } catch (Exception){
                throw new InvalidResourceException('Invalid From date');
            }

            try {
                $toDate = new \DateTime($to);
            } catch (Exception){
                throw new InvalidResourceException('Invalid To date');
            }

            $fromDateWithHour = $fromDate->format($dateFormat);
            $toDateWithHour   = $toDate->format($dateFormat);

            $queryUsers = $this->entityManager->createQueryBuilder()
                ->select('upc')
                ->from(UserProfileCache::class, 'upc')
                ->where($this->entityManager->createQueryBuilder()->expr()->between(
                    'upc.last_user_date_updated',
                    ':from',
                    ':to'
                ))
                ->setParameters( ['from' => $fromDateWithHour, 'to' => $toDateWithHour] )
                ->addOrderBy('upc.last_user_date_updated','ASC')
                ->getQuery();

            $paginator = new Paginator($queryUsers);
            $paginator->getQuery()
                ->setFirstResult($maxResult * ($pageNum - 1))
                ->setMaxResults($maxResult);

            $numberOfPages =  ceil($paginator->count() / $maxResult);

            /** @var array $updatedUsers */
            $updatedUsers = $paginator->getQuery()->execute();

            $ESBFormattedUpdatedUsers['number_of_pages'] = $numberOfPages;
        } else {
            /** @var array $updatedUsers */
            $updatedUsers = $this->entityManager
                ->getRepository(UserProfileCache::class)
                ->findBy(['is_updated' => true]);
        }

        $ESBFormattedUpdatedUsers['correlationKey'] = 'study-'.mktime(date("H"));
        $ESBFormattedUpdatedUsers['users'] = [];
        if ($from)  $ESBFormattedUpdatedUsers['number_of_pages'] = $numberOfPages;

        $propertyMapping = [
                "firstname"                 => "first_name",
                "last_user_date_updated"    => "last_user_date_updated",
                "lastname"                  => "last_name",
                "organization_title"        => "work_exp_company_title",
                "organization_id"           => "work_exp_company_id",
                "cell_phone"                => "cell_phone",
                "cell_phone_prefix"         => "cell_phone_prefix",
                "personal_phone"            => "personal_phone",
                "personal_phone_prefix"     => "personal_phone_prefix",
                "preferred_phone"           => "preferred_phone",
                "personal_email"            => "personal_email",
                "preferred_email"           => "preferred_email",
                "address_line_1"            => "work_exp_company_address_line_1",
                "address_line_2"            => "work_exp_company_address_line_2",
                "address_line_3"            => "work_exp_company_address_line_3",
                "state"                     => "work_exp_company_address_state",
                "postal_code"               => "work_exp_company_postal_code",
                "work_country"              => "work_exp_job_country",
                "country_code"              => "work_exp_company_country",
                "city"                      => "work_exp_company_city",
                "job_title"                 => "work_exp_job_title",
                "work_phone"                => "work_phone",
                "work_phone_prefix"         => "work_phone_prefix",
                "work_email"                => "work_email",
                "is_new_work_exp"           => "work_exp_is_new",
                "is_main"                   => "work_exp_current_job"
        ];

        /** @var UserProfileCache $userProfileCache */
        foreach($updatedUsers as $userProfileCache){

            $mappedUpdatedFields = ["emplid"];

            if ($userProfileCache->getUpdatedFields()) {
                $updatedFields = $userProfileCache->getUpdatedFields();
                if (strlen($updatedFields) > 0) {
                    $updatedFieldsArray = explode(",", $updatedFields);
                    foreach ($updatedFieldsArray as $dbField) {
                        if (array_key_exists($dbField, $propertyMapping)) {
                            if (is_array($propertyMapping[$dbField])) {
                                foreach ($propertyMapping[$dbField] as $tempMappedField) {
                                    array_push($mappedUpdatedFields, $tempMappedField);
                                }
                            } else {
                                array_push($mappedUpdatedFields, $propertyMapping[$dbField]);
                            }
                        }
                    }
                }
            }

            if (count($mappedUpdatedFields) <= 1) {
                if (!$from) {
                    $userProfileCache->setProfileUpdated(false);
                    $this->entityManager->persist($userProfileCache);
                }

                continue;
            }

            $ESBFormattedUpdatedUsers['users'][] = array_filter(
                [
                    "emplid" => $userProfileCache->getUser()->getPeoplesoftId(),
                    "first_name" => $userProfileCache->getFirstname(),
                    "last_name" => $userProfileCache->getLastname(),
                    "work_exp_company_title" => $userProfileCache->getOrganizationTitle(),
                    "work_exp_company_id" => ($userProfileCache->getOrganizationId() ?? ''),
                    "email_address" => $userProfileCache->getUpnEmail(),
                    "nationalities" => array_map('trim', explode(',', $userProfileCache->getNationality())),
                    "cell_phone" => $userProfileCache->getCellPhone(),
                    "cell_phone_prefix" => $userProfileCache->getCellPhonePrefix(),
                    "personal_phone" => $userProfileCache->getPersonalPhone(),
                    "personal_phone_prefix" => $userProfileCache->getPersonalPhonePrefix(),
                    "preferred_phone" => ($userProfileCache->getPreferredPhone() !== null ? $this->preferred_phone_choices[$userProfileCache->getPreferredPhone()] : null),
                    "personal_email" => $userProfileCache->getPersonalEmail(),
                    "preferred_email" => ($userProfileCache->getPreferredEmail() !== null ? $this->preferred_email_choices[$userProfileCache->getPreferredEmail()] : null),
                    "constituent_types" => array_map('trim', explode(',', $userProfileCache->getConstituentTypes())),
                    "work_exp_company_address_line_1" => $userProfileCache->getAddressLine1(),
                    "work_exp_company_address_line_2" => $userProfileCache->getAddressLine2(),
                    "work_exp_company_address_line_3" => $userProfileCache->getAddressLine3(),
                    "work_exp_company_address_state" => $userProfileCache->getState(),
                    "work_exp_company_postal_code" => $userProfileCache->getPostalCode(),
                    "work_exp_company_country" => $userProfileCache->getCountryCode(),
                    "work_exp_company_city" => $userProfileCache->getCity(),
                    "work_exp_job_title" => $userProfileCache->getJobTitle(),
                    "work_exp_current_job" => true,
                    "work_phone" => $userProfileCache->getWorkPhone(),
                    "work_phone_prefix" => $userProfileCache->getWorkPhonePrefix(),
                    "work_email" => $userProfileCache->getWorkEmail(),
                    "work_exp_job_country" => $userProfileCache->getCountryCode(),
                    "work_exp_is_new" => $userProfileCache->getWorkExperienceStatus(),
                ],
                fn($key) => in_array($key, $mappedUpdatedFields),
                ARRAY_FILTER_USE_KEY
            );

            if (!$from) {
                $userProfileCache->setProfileUpdated(false);
                $userProfileCache->setWorkExperienceStatus(false);
                $userProfileCache->setLastESBDteProcessed((new \DateTime())->setTimezone(new \DateTimeZone('UTC')));
                $this->entityManager->persist($userProfileCache);
            }

            $this->log("[ESB] PSOftID: ".$userProfileCache->getUser()->getPeoplesoftId()." has been fetch with correlation key: ".$ESBFormattedUpdatedUsers['correlationKey']);
        }

        if (!$from) $this->entityManager->flush();

        return $ESBFormattedUpdatedUsers;
    }

    /**
     * Handler for receiving the list of updated/new profile from ESB.
     * This is used by ESB to push updated/new profiles
     *
     *
     * @return string
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidResourceException
     */
    public function profilesReceive(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        return $this->saveESBWithPayload($payload);
    }

    /**
     * Accepts the payload coming ESB and save it to cache and core (core is present)
     *
     * @param $payload
     *
     * @return mixed
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveESBWithPayload($payload)
    {
        if (!$payload){
            $this->log("[ESB] No content / Unable to parse content. \r\n[ESB - payload] ".json_encode($payload));
            throw new InvalidResourceException('[ESB] No content / Unable to parse content.');
        } else {
            if (!array_key_exists('users', $payload)) {
                $this->log("[ESB] User key not existing. \r\n[ESB - payload] ".json_encode($payload));
                throw new InvalidResourceException('[ESB] User key not existing');
            } elseif (!array_key_exists('correlationKey', $payload)) {
                $this->log("[ESB] correlationKey key not existing. \r\n[ESB - payload] ".json_encode($payload));
                throw new InvalidResourceException('[ESB] correlationKey key not existing');
            } else {
                $replyToESB['correlationKey'] = $payload['correlationKey'];
                $replyToESB['users'] = [];
                foreach ($payload['users'] as $esbUser) {

                    $errorMessages = [];

                    if (array_key_exists('cell_phone_prefix', $esbUser)) {
                        if (strlen((string) $esbUser['cell_phone_prefix']) > 0) {
                            $esbUser['cell_phone_prefix'] = str_replace('+', '', $esbUser['cell_phone_prefix']);
                        }
                    }

                    if (array_key_exists('personal_phone_prefix', $esbUser)) {
                        if (strlen((string) $esbUser['personal_phone_prefix']) > 0) {
                            $esbUser['personal_phone_prefix'] = str_replace('+', '', $esbUser['personal_phone_prefix']);
                        }
                    }

                    if (array_key_exists('work_phone_prefix', $esbUser)) {
                        if (strlen((string) $esbUser['work_phone_prefix']) > 0) {
                            $esbUser['work_phone_prefix'] = str_replace('+', '', $esbUser['work_phone_prefix']);
                        }
                    }

                    $collectionConstraint = $this->esbProfileConstraintsValidation();

                    $validatorErrors = $this->validator->validate($esbUser, $collectionConstraint);

                    $dependencyError = [];
                    
                    if (array_key_exists('work_exp_company_id',$esbUser)){
                        if (strlen((string) $esbUser['work_exp_company_id']) > 0) {
                            $organization_id = trim((string) $esbUser['work_exp_company_id']);
                            $study_organization = $this->entityManager
                                ->getRepository(Organization::class)
                                ->findOneBy(['ext_org_id' => $organization_id]);

                            if (!$study_organization) {
                                $this->log("Adding new Organization ID: $organization_id from user object payload: " . $payload['correlationKey']);
                                $study_organization = new Organization();
                            }

                            $organization_obj['ext_org_id'] = $organization_id;
                            $organization_obj['title']      = trim((string) $esbUser['work_exp_company_title']);
                            $this->organizationManager->setOrganizationValues($study_organization, $organization_obj);

                            $this->entityManager->persist($study_organization);
                            $this->entityManager->flush($study_organization);
                        }
                    }

                    if (array_key_exists('phone_numbers',$dependencyError)){
                        if (count($dependencyError['phone_numbers']) > 0){
                            foreach($dependencyError['phone_numbers'] as $dependencyErrorMessage){
                                $this->addToErrorValidations($errorMessages,$dependencyErrorMessage);
                            }
                        }
                    }

                    if (count($validatorErrors) > 0 || count($errorMessages) > 0) {
                        foreach ($validatorErrors as $errors)
                            $this->addToErrorValidations($errorMessages, str_replace('"','',$errors->getMessage()));

                        $this->log("[ESB] There was an error receiving the data \r\n[ESB - block payload] ".json_encode($esbUser)."\r\n[Error Messages] ".json_encode($errorMessages));

                        if (array_key_exists('emplid', $esbUser)) {
                            if (strlen((string) $esbUser['emplid']) > 0) {
                                $cleanedMessageToLog = $errorMessages;
                                unset($cleanedMessageToLog['error']);

                                $mktime = mktime(date("H"));
                                // log to S3
                                $person = ["esb_correlationKey" => $payload['correlationKey'], "unix_timestamp"     => $mktime, "timestamp"          => date("F j, Y H:i:s e", $mktime), "peoplesoft_id"      => $esbUser['emplid'], "error_messages"     => $cleanedMessageToLog];

                                $result = $this->s3->uploadToS3(
                                    "esb-error-messages/" . trim((string) $esbUser['emplid']) . ".json",
                                    json_encode($person,JSON_PRETTY_PRINT),
                                    true
                                );

                                $this->log( "[ESB] an error message has been logged to S3 for ".$esbUser['emplid']." " . json_encode($result,JSON_PRETTY_PRINT) );
                            }
                        }

                        $errorMessages['emplid'] = ($esbUser['emplid'] ?: '');
                        array_push($replyToESB['users'],  $errorMessages);
                    } else {
                        $result = $this->s3->removeFromS3(
                            "esb-error-messages/" . trim((string) $esbUser['emplid']) . ".json",
                            true
                        );

                        $this->log( "[ESB] Removing error message from S3 with PSoft ID:  ".$esbUser['emplid']." " . json_encode($result,JSON_PRETTY_PRINT) );

                        //Convert to int for Study format
                        if (array_key_exists('preferred_email', $esbUser)) {
                            if (strlen((string) $esbUser['preferred_email']) > 0) {
                                $esbUser['preferred_email'] = match (trim((string) $esbUser['preferred_email'])) {
                                    'HOME' => 0,
                                    'BUSN' => 1,
                                    default => -1,
                                };
                            } else {
                                $esbUser['preferred_email'] = -1;
                            }
                        }

                        //Convert to int for Study format
                        if (array_key_exists('preferred_phone', $esbUser)) {
                            if (strlen((string) $esbUser['preferred_phone']) > 0) {
                                $esbUser['preferred_phone'] = match (trim((string) $esbUser['preferred_phone'])) {
                                    'HOME' => 0,
                                    'BUSN' => 1,
                                    'BMOB' => 2,
                                    default => -1,
                                };
                            } else {
                                $esbUser['preferred_phone'] = -1;
                            }
                        }

                        //check if we have a user for this PSOFT ID
                        $user = $this->entityManager->getRepository(User::class)
                            ->findOneBy([
                                "peoplesoft_id" => $esbUser['emplid']
                            ]);

                        if (!$user) {
                            $user = new User();
                            $user->setPeoplesoftId($esbUser['emplid']);
                            $this->entityManager->persist($user);
                        }

                        /** @var UserProfileCache $cacheUser */
                        $cacheUser = $this->getUserFromCache($user->getPeoplesoftId());
                        /** @var UserProfile $coreUser */
                        $coreUser = $this->getUserFromCore($user->getPeoplesoftId());

                        if (!$cacheUser)
                            $cacheUser = new UserProfileCache();

                        $attributesToUpdate = [];
                        $attributesToUpdate[] = ['setUser' => $user];

                        $entity_pair_setter = [
                            [ 'key' => 'first_name',                                'fn' => 'setFirstname' ],
                            [ 'key' => 'last_name',                                 'fn' => 'setLastname' ],
                            [ 'key' => 'email_address',                             'fn' => 'setUpnEmail' ],
                            [ 'key' => 'nationalities',                             'fn' => 'setNationality' ],
                            [ 'key' => 'constituent_types',                         'fn' => 'setConstituentTypes' ],

                            [ 'key' => 'cell_phone',                                'fn' => 'setCellPhone' ],
                            [ 'key' => 'cell_phone_prefix',                         'fn' => 'setCellPhonePrefix' ],
                            [ 'key' => 'personal_phone',                            'fn' => 'setPersonalPhone' ],
                            [ 'key' => 'personal_phone_prefix',                     'fn' => 'setPersonalPhonePrefix' ],
                            [ 'key' => 'work_phone',                                'fn' => 'setWorkPhone' ],
                            [ 'key' => 'work_phone_prefix',                         'fn' => 'setWorkPhonePrefix' ],
                            [ 'key' => 'preferred_phone',                           'fn' => 'setPreferredPhone' ],

                            [ 'key' => 'work_email',                                'fn' => 'setWorkEmail' ],
                            [ 'key' => 'personal_email',                            'fn' => 'setPersonalEmail' ],
                            [ 'key' => 'preferred_email',                           'fn' => 'setPreferredEmail' ],

                            [ 'key' => 'work_exp_job_title',                        'fn' => 'setJobTitle' ],
                            [ 'key' => 'work_exp_company_title',                    'fn' => 'setOrganizationTitle' ],
                            [ 'key' => 'work_exp_company_id',                       'fn' => 'setOrganizationId' ],
                            [ 'key' => 'work_exp_company_address_line_1',           'fn' => 'setAddressLine1' ],
                            [ 'key' => 'work_exp_company_address_line_2',           'fn' => 'setAddressLine2' ],
                            [ 'key' => 'work_exp_company_address_line_3',           'fn' => 'setAddressLine3' ],
                            [ 'key' => 'work_exp_company_address_state',            'fn' => 'setState' ],
                            [ 'key' => 'work_exp_company_address_postal_code',      'fn' => 'setPostalCode' ],
                            [ 'key' => 'work_exp_company_address_country',          'fn' => 'setCountryCode' ],
                            [ 'key' => 'work_exp_company_address_city',             'fn' => 'setCity' ],

                            [ 'key' => 'work_exp_job_country',                      'fn' => 'setCountry' ],
                        ];

                        foreach($entity_pair_setter as $obj_setter) {
                            $key    = $obj_setter['key'];
                            $setter = $obj_setter['fn'];
                            if (array_key_exists($key, $esbUser)) {
                                if (is_null($esbUser[$key])) {
                                    $esbUser[$key] = '';
                                }
                                switch($setter) {
                                    case "setConstituentTypes":

                                        $constituentTypes = [];
                                        if ($cacheUser->getConstituentTypes()){
                                            $constituentTypes = array_map('trim',explode(",",$cacheUser->getConstituentTypes()));
                                        }

                                        foreach ($esbUser[ 'constituent_types' ] as $constituentTypeObj) {
                                            $id     = $constituentTypeObj['id'];
                                            $status = $constituentTypeObj['status'];
                                            $past   = $constituentTypeObj['past'];

                                            if ($status == "deleted" || $past) {
                                                if (($key = array_search($id, $constituentTypes)) !== false) {
                                                    unset($constituentTypes[$key]);
                                                }
                                            } else {
                                                array_push($constituentTypes,$id);
                                            }
                                        }

                                        $constituentTypes = array_unique($constituentTypes);
                                        array_push($attributesToUpdate, ['setConstituentTypes' => implode(", ", $constituentTypes)]);
                                        break;

                                    case 'setNationality' :
                                        if (count($esbUser['nationalities']) > 0) {
                                            $nationalities = [];
                                            if ($cacheUser->getNationality()){
                                                $strNationalities = trim($cacheUser->getNationality());
                                                if (strlen($strNationalities) > 1) $nationalities = array_map('trim',explode(",",$cacheUser->getNationality()));
                                            }
                                            
                                            foreach ($esbUser[ 'nationalities' ] as $nationalityObj) {
                                                $id     = $nationalityObj['id'];
                                                $status = $nationalityObj['status'];

                                                if ($status == "deleted") {
                                                    if (($key = array_search($id, $nationalities)) !== false) {
                                                        unset($nationalities[$key]);
                                                    }
                                                } else {
                                                    $nationalities[] = $id;
                                                }
                                            }

                                            $nationalities = array_unique($nationalities);

                                            $attributesToUpdate[] = ['setNationality' => implode(", ", $nationalities)];
                                        }

                                        break;

                                    default:
                                        $attributesToUpdate[] = [$setter => $esbUser[$key]];
                                }
                            }
                        }

                        if (count($attributesToUpdate) > 0){
                            foreach ($attributesToUpdate as $attributeValue){
                                $setterName = key($attributeValue);

                                if ($cacheUser)
                                    $cacheUser->$setterName($attributeValue[$setterName]);

                                if ($coreUser) {
                                    if ($setterName == 'setJobTitle'){
                                        if (strlen((string) $attributeValue[$setterName]) > 0) {
                                            if ($coreUser->getJobTitle()) {
                                                if ($coreUser->getJobTitle() != $attributeValue[$setterName]) {
                                                    $coreUser->$setterName($attributeValue[$setterName]);
                                                    $coreUser->setPreferredJobTitle($attributeValue[$setterName]);
                                                }
                                            } else {
                                                if(!$coreUser->getPreferredJobTitle()){
                                                    $coreUser->setPreferredJobTitle($attributeValue[$setterName]);
                                                }
                                                $coreUser->$setterName($attributeValue[$setterName]);
                                            }
                                        }
                                    } else {
                                        $coreUser->$setterName($attributeValue[$setterName]);
                                    }
                                }
                            }

                            if ($cacheUser) {
                                $this->entityManager->persist($cacheUser);
                                $this->huddleUserManager->prepareVanillaUserInfo($this->formatUserProfileToStudy($cacheUser));
                            }

                            if ($coreUser)
                                $this->entityManager->persist($coreUser);

                        }

                        $this->barcoManager->updateBarcoUserFromAIP($esbUser['emplid']);
                        $replyToESB['users'][] = ['emplid' => $esbUser['emplid'], 'success' => true];
                    }
                }

                $this->entityManager->flush();

                return $replyToESB;
            }
        }
    }

    /**
     * Handler to copy user cache details to core
     *
     *
     * @return bool
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function copyToCoreProfile(User $user){
        $this->log("Psoft: ".$user->getPeoplesoftId()." starting to copy from cache >>> core");
        $userProfileCache = $user->getCacheProfile();

        if (!$userProfileCache) return false;

        $entity_pair_setter = [
            [ 'key' => 'getFirstname',           'fn' => 'setFirstname' ],
            [ 'key' => 'getLastname',            'fn' => 'setLastname' ],
            [ 'key' => 'getUpnEmail',            'fn' => 'setUpnEmail' ],
            [ 'key' => 'getNationality',         'fn' => 'setNationality' ],
            [ 'key' => 'getConstituentTypes',    'fn' => 'setConstituentTypes' ],

            [ 'key' => 'getCellPhone',           'fn' => 'setCellPhone' ],
            [ 'key' => 'getCellPhonePrefix',     'fn' => 'setCellPhonePrefix' ],
            [ 'key' => 'getPersonalPhone',       'fn' => 'setPersonalPhone' ],
            [ 'key' => 'getPersonalPhonePrefix', 'fn' => 'setPersonalPhonePrefix' ],
            [ 'key' => 'getWorkPhone',           'fn' => 'setWorkPhone' ],
            [ 'key' => 'getWorkPhonePrefix',     'fn' => 'setWorkPhonePrefix' ],
            [ 'key' => 'getPreferredPhone',      'fn' => 'setPreferredPhone' ],

            [ 'key' => 'getWorkEmail',           'fn' => 'setWorkEmail' ],
            [ 'key' => 'getPersonalEmail',       'fn' => 'setPersonalEmail' ],
            [ 'key' => 'getPreferredEmail',      'fn' => 'setPreferredEmail' ],

            [ 'key' => 'getJobTitle',            'fn' => 'setJobTitle' ],
            [ 'key' => 'getOrganizationTitle',   'fn' => 'setOrganizationTitle' ],
            [ 'key' => 'getOrganizationId',      'fn' => 'setOrganizationId' ],
            [ 'key' => 'getAddressLine1',        'fn' => 'setAddressLine1' ],
            [ 'key' => 'getAddressLine2',        'fn' => 'setAddressLine2' ],
            [ 'key' => 'getAddressLine3',        'fn' => 'setAddressLine3' ],
            [ 'key' => 'getState',               'fn' => 'setState' ],
            [ 'key' => 'getPostalCode',          'fn' => 'setPostalCode' ],
            [ 'key' => 'getCountryCode',         'fn' => 'setCountryCode' ],
            [ 'key' => 'getCity',                'fn' => 'setCity' ],

            [ 'key' => 'getCountry',             'fn' => 'setCountry' ],
        ];

        $coreUser = $this->getUserFromCore($user->getPeoplesoftId());
        if (!$coreUser){
            $coreUser = new UserProfile();
            $coreUser->setUser($user);
        }

        foreach($entity_pair_setter as $obj_setter) {
            $key = $obj_setter['key'];
            $setter = $obj_setter['fn'];

            if ($userProfileCache->$key()) {
                $coreUser->$setter($userProfileCache->$key());

                if ($setter == 'setJobTitle'){
                    $coreUser->setPreferredJobTitle($userProfileCache->$key());
                }
            }
        }

        $this->entityManager->persist($coreUser);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Handler for receiving the list of updated test profile from ESB.
     *
     *
     * @return string
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PermissionDeniedException
     * @throws InvalidResourceException
     */
    public function testProfilesReceive(Request $request)
    {
        if ($this->environment === 'dev' || $this->environment === 'int' || $this->environment === 'uat') {
            $payload = json_decode($request->getContent(), true);
            if (!$payload) {
                $this->log("[ESB - Test] No content / Unable to parse content. \r\n[ESB - payload] " . json_encode($request->getContent()));
                throw new InvalidResourceException('[ESB] No content / Unable to parse content.');
            } else {
                if (!array_key_exists('users', $payload)) {
                    $this->log("[ESB - Test] User key not existing. \r\n[ESB - payload] " . json_encode($request->getContent()));
                    throw new InvalidResourceException('[ESB] User key not existing"');
                } elseif (!array_key_exists('correlationKey', $payload)) {
                    $this->log("[ESB - Test] correlationKey key not existing. \r\n[ESB - payload] " . json_encode($request->getContent()));
                    throw new InvalidResourceException('[ESB] correlationKey key not existing"');
                } else {
                    $replyToESB['correlationKey'] = $payload['correlationKey'];
                    $replyToESB['users'] = [];
                    foreach ($payload['users'] as $esbUser) {
                        if (!array_key_exists('emplid', $esbUser)) {
                            $replyToESB['users'][] = [
                                [
                                    'emplid' => '',
                                    'error' => 'error', ['emplid parameter not exists']
                                ]
                            ];

                        } else {
                            //check if we have a user for this PSOFT ID
                            $user = $this->entityManager->getRepository(User::class)
                                ->findOneBy([
                                    "peoplesoft_id" => $esbUser['emplid']
                                ]);

                            if (!$user) {
                                $replyToESB['users'][] = [
                                    [
                                        'emplid' => $esbUser['emplid'],
                                        'error' => 'error', ['user not yet exists']
                                    ]
                                ];
                                array_push($replyToESB['emplid'], $esbUser['emplid']);
                            } else {
                                $cacheUser = $this->getUserFromCache($user->getPeoplesoftId());
                                $coreUser  = $this->getUserFromCore($user->getPeoplesoftId());

                                if ($cacheUser) {
                                    $cacheUser->setProfileUpdated(true);

                                    $paramKeyArray = [
                                        ['key' => 'first_name',             'fn' => 'setFirstname'],
                                        ['key' => 'last_name',              'fn' => 'setLastname'],
                                        ['key' => 'last_user_date_updated', 'fn' => 'setLastUserDateUpdated']
                                    ];

                                    foreach ($paramKeyArray as $paramKey) {
                                        $key = $paramKey['key'];
                                        $setter = $paramKey['fn'];
                                        if (array_key_exists($key, $esbUser)) {
                                            if (strlen((string) $esbUser[$key]) > 0) {
                                                $cacheUser->$setter($esbUser[$key]);
                                                if ($coreUser)
                                                    $coreUser->$setter($esbUser[$key]);
                                            }
                                        }
                                    }
                                    $this->entityManager->persist($cacheUser);
                                    if ($coreUser)
                                        $this->entityManager->persist($coreUser);

                                    $replyToESB['users'][] = ['emplid' => $esbUser['emplid'], 'success' => true];
                                } else {
                                    $replyToESB['users'][] = [
                                        [
                                            'emplid' => $esbUser['emplid'],
                                            'error' => 'error', ['User not yet cached. Please post the complete profile first.']
                                        ]
                                    ];
                                }
                            }
                        }
                    }

                    $this->entityManager->flush();

                    return $replyToESB;
                }
            }
        } else {
            throw new PermissionDeniedException();
        }
    }

    private function addToErrorValidations(&$errorMessages, $message){
        if (!array_key_exists('errors', $errorMessages)){
            $errorMessages['errors'] = [];
        }

        array_push($errorMessages['errors'], $message);
    }

    /**
     * Collection from Study
     * @return Collection
     */
    public function profileConstraintsValidation(){
        return new Collection(
            ['fields' => ['first_name'            => ($this->aip_enabled === 'YES' ? [new NotBlank(['message'=>'Personal : First name should not be blank']), new Length(['max' => 30, 'maxMessage'=> 'Personal : First name should be 30 characters or less']), new Regex(['pattern' => '/^[A-Za-z\'-.,ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ ]+$/', 'message'=> 'Personal : Please review the field(s) before saving'])] : [new NotBlank(['message'=>'Personal : First name should not be blank'])] ), 'last_name'             =>  ($this->aip_enabled === 'YES' ? [new NotBlank(['message'=>'Personal : Last name should not be blank']), new Length(['max' => 30, 'maxMessage'=> 'Personal : Last name should be 30 characters or less']), new Regex(['pattern' => '/^[A-Za-z\'-.,ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ ]+$/', 'message'=> 'Personal : Please review the field(s) before saving'])] : [new NotBlank(['message'=>'Personal : Last name should not be blank'])] ), 'linkedin_id'           => [], 'work_email'            => ($this->aip_enabled === 'YES' ? [new Email(['message'=>'Contact : Please provide a valid Work email address']), new Length(['max' => 70, 'maxMessage'=> 'Contact : Work email address should be 70 characters or less'])] : [new Email(['message'=>'Contact : Please provide a valid Work email address'])] ), 'personal_email'        => ($this->aip_enabled === 'YES' ? [new Email(['message'=>'Contact : Please provide a valid Personal email address']), new Length(['max' => 70, 'maxMessage'=> 'Contact : Personal email address should be 70 characters or less'])] : [new Email(['message'=>'Contact : Please provide a valid Personal email address'])] ), 'personal_phone'        => [new Length(['max' => 24, 'maxMessage'=> 'Contact : Personal phone should be 24 characters or less'])], 'work_phone'            => [new Length(['max' => 24, 'maxMessage'=> 'Contact : Work phone should be 24 characters or less'])], 'cell_phone'            => [new Length(['max' => 24, 'maxMessage'=> 'Contact : Mobile phone should be 24 characters or less'])], 'personal_phone_prefix' => [new Regex(['pattern' => '/^[0-9\-]{1,}$/', 'message'=> 'Contact : Please provide a valid Country Code'])], 'work_phone_prefix'     => [new Regex(['pattern' => '/^[0-9\-]{1,}$/', 'message'=> 'Contact : Please provide a valid Country Code'])], 'cell_phone_prefix'     => [new Regex(['pattern' => '/^[0-9\-]{1,}$/', 'message'=> 'Contact : Please provide a valid Country Code'])], 'preferred_phone'       => [new Choice(['choices' => [0, 1, 2], 'message' => 'Contact : Please select one preferred phone number.'])], 'preferred_email'       => [new Choice(['choices' => [0, 1], 'message' => 'Contact : Please select a preferred email.'])], 'bio'                   => [new Length(['max' => 2000, 'maxMessage'=> 'Personal : Bio should be 2000 characters or less.'])], 'job'                   => []], 'allowMissingFields' => true, 'allowExtraFields'   => true]
        );
    }

    /**
     * @return Collection
     */
    public function jobConstraintsValidation(){
        return new Collection(
            ['fields' => ['job_action'        => [], 'title'             => [new NotBlank(['message'=>'Professional : Job Title should not be blank']), new Length(['max' => 120, 'maxMessage'=> 'Professional : Job Title should be 120 characters or less']), new Regex(['pattern' => '/^[A-Za-z\'-.,ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ ]+$/', 'message'=> 'Professional : Please review the field(s) before saving'])], 'description'       => [], 'organisation'      => [new NotBlank(['message'=>'Professional : Company should not be blank']), new Length(['max' => 50, 'maxMessage'=> 'Professional : Company name should be 50 characters or less'])], 'address_line_1'    => [new NotBlank(['message'=>'Professional : Job Address should not be blank']), new Length(['max' => 55, 'maxMessage'=> 'Professional : Job Address 1 should be 55 characters or less'])], 'address_line_2'    => [new Length(['max' => 55, 'maxMessage'=> 'Professional : Job Address 2 should be 55 characters or less'])], 'address_line_3'    => [new Length(['max' => 55, 'maxMessage'=> 'Professional : Job Address 3 should be 55 characters or less'])], 'state'             => [new Length(['max' => 8, 'maxMessage'=> 'Professional : Job State should be 8 characters or less'])], 'postal_code'       => [new Length(['max' => 12, 'maxMessage'=> 'Professional : Postal code should be 12 characters or less'])], 'country'           => [new NotBlank(['message'=>'Professional : Country should not be blank'])], 'city'              => [new Length(['max' => 30, 'maxMessage'=> 'Professional : City should be 30 characters or less'])]], 'allowMissingFields' => true, 'allowExtraFields'   => true]
        );
    }

    /**
     * Collection from ESB
     * @return Collection
     */
    public function esbProfileConstraintsValidation(){
        return new Collection(
            ['fields' => ['emplid'                    => [new NotNull(['message'  =>'Personal : Field[emplid] EmplId not be null']), new NotBlank(['message' =>'Personal :  Field[emplid] EMPLID should not be blank'])], 'email_address'             => [new NotBlank(['message'=>'Professional : Field[email_address] UPN should not be blank'])], 'constituent_types'         => [new NotBlank(['message'=>'Professional : Field[constituent_types] should not be blank'])], 'first_name'                => [new NotBlank(['message'=>'Personal :  Field[first_name] First name should not be blank']), new Length(['max' => 30, 'maxMessage'=> 'Personal : Field[first_name] First name should be 30 characters or less'])], 'last_name'                 => [new NotBlank(['message'=>'Personal :  Field[last_name] Last name should not be blank']), new Length(['max' => 30, 'maxMessage'=> 'Personal : Field[last_name] Last name should be 30 characters or less'])], 'work_exp_job_title'       => new Optional([new Length(['max' => 120, 'maxMessage'=> 'Professional : Field[work_exp_job_title] Job Title should be 120 characters or less'])]), 'work_exp_company_title'   => new Optional([new Length(['max' => 50, 'maxMessage'=> 'Professional : Field[work_exp_company_title] Company Title should be 50 characters or less'])]), 'work_exp_company_id'      => new Optional([]), 'nationalities'            => new Optional([]), 'preferred_phone'          => new Optional([]), 'preferred_email'          => new Optional([]), 'cell_phone'               => new Optional([]), 'cell_phone_prefix'        => new Optional([new Regex(['pattern' => '/^[0-9]{1,}$/', 'message'=> 'Contact : Field[cell_phone_prefix] Please provide a valid Country Code'])]), 'personal_phone'           => new Optional([]), 'personal_phone_prefix'    => new Optional([new Regex(['pattern' => '/^[0-9]{1,}$/', 'message'=> 'Contact : Field[personal_phone_prefix] Please provide a valid Country Code'])]), 'personal_email'           => new Optional([new Email(['message'=>'Contact : Field[personal_email] Please provide a valid Work email address']), new Length(['max' => 70, 'maxMessage'=> 'Contact : Field[personal_email] Work email address should be 70 characters or less'])]), 'work_exp_company_address_line_1'    => new Optional([new Length(['max' => 55, 'maxMessage'=> 'Professional : Field[work_exp_company_address_line_1] Job Address 1 should be 55 characters or less'])]), 'work_exp_company_address_line_2'       => new Optional([new Length(['max' => 55, 'maxMessage'=> 'Professional : Field[work_exp_company_address_line_2] Job Address 2 should be 55 characters or less'])]), 'work_exp_company_address_line_3'       => new Optional([new Length(['max' => 55, 'maxMessage'=> 'Professional : Field[work_exp_company_address_line_3] Job Address 3 should be 55 characters or less'])]), 'work_exp_company_address_state'        => new Optional([]), 'work_exp_company_address_postal_code'  => new Optional([new Length(['max' => 12, 'maxMessage'=> 'Professional : Field[work_exp_company_address_postal_code] Postal code should be 12 characters or less'])]), 'work_exp_company_address_country'           => new Optional(new Length(['max' => 4, 'maxMessage'=> 'Professional : Field[work_exp_company_address_country] City should be 4 characters or less'])), 'work_exp_company_address_city'              => new Optional([new Length(['max' => 30, 'maxMessage'=> 'Professional : Field[work_exp_company_address_city] City should be 30 characters or less'])]), 'work_exp_current_job'      => new Optional([]), 'work_phone'                => new Optional([]), 'work_phone_prefix'         => new Optional([new Regex(['pattern' => '/^[0-9]{1,}$/', 'message'=> 'Contact : Field[work_phone_prefix] Please provide a valid Country Code'])]), 'work_email'                => new Optional([new Email(['message'=>'Contact : Field[work_email] Please provide a valid Work email address']), new Length(['max' => 70, 'maxMessage'=> 'Contact : Field[work_email] Work email address should be 70 characters or less'])]), 'work_exp_job_country'      => new Optional([new Length(['max' => 3, 'maxMessage'=> 'Contact : Field[work_email] Work email address should be 3 characters or less'])]), 'work_exp_is_new'           => new Optional([])], 'allowMissingFields' => false, 'missingFieldsMessage' => "Missing required field: {{ field }}", 'extraFieldsMessage'   => "Extra field: {{ field }}"]
        );
    }

    /**
     * Prepare Request Parameter
     *
     * @param array $request Request parameters
     *
     * @return array
     */
    public function addRequestParamToArray($request)
    {
        $data   = [];
        $params = ['first_name', 'last_name', 'bio', 'linkedin_id', 'work_email', 'personal_email', 'personal_phone', 'work_phone', 'cell_phone', 'personal_phone_prefix', 'work_phone_prefix', 'cell_phone_prefix', 'preferred_phone', 'preferred_email', 'job', 'hide_phone', 'hide_email'];

        foreach ($params as $param) {
            if (array_key_exists($param, $request)) {
                $data[$param] = $request[$param];
            }
        }

        return $data;
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

    /**
     * Function that resize the image
     *
     * @param $path
     * @throws InvalidResourceException
     */
    private function imageResize($path){
        $pic_limit = 2; //2MB
        $fileSizeLimit = ($pic_limit *1024*1024);

        try {
            $resize = new \Imagick();
            $resize->readImage($path);
            $resize->resizeImage(600, 600, \Imagick::FILTER_LANCZOS, 1, true);
            $resize->writeImage($path);
            $newImageSize = $resize->getImageLength();
            $resize->clear();
            if ($newImageSize > $fileSizeLimit) {
                $this->imageResize($path);
            }

        } catch (Exception $e){
            $this->log("Error occurred during Profile Update: " . json_encode($e->getmessage()));
            $errorMessages[ 'picture_data' ] = ['Personal: Upload Image Error'];
            throw new InvalidResourceException($errorMessages);
        }
    }

    /**
     * Handler functions to Update profile to show hide contact from study admin
     *
     * @param Request $request Expects header parameter
     *
     * @return mixed
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     *
     */
    public function updateUserProfileContactStatus(Request $request, $psoftId, $contactType, $hideStatus)
    {
        //check if we have a user for this PSOFT ID
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy([
                "peoplesoft_id" => $psoftId
            ]);

        if (!$user) {
            $errorMessage = "[updateUserProfileContactStatus] Unable to find user $psoftId";
            $this->log($errorMessage);
            throw new InvalidResourceException($errorMessage);
        } else {
            /** @var UserProfile $userProfiles */
            $userProfiles = $user->getCoreProfile();
            if ($userProfiles) {
                if ($contactType === 'email') {
                    $userProfiles->setHideEmail($hideStatus);
                } else if ($contactType === 'phone') {
                    $userProfiles->setHidePhone($hideStatus);
                    $this->entityManager->persist($userProfiles);
                    $this->entityManager->flush();
                } else {
                    $errorMessage = "[updateUserProfileContactStatus] Invalid type: $contactType for psoftid $psoftId";
                    $this->log($errorMessage);
                    throw new InvalidResourceException($errorMessage);
                }

                $this->entityManager->persist($userProfiles);
                $this->entityManager->flush();

                return $userProfiles;
            } else {
                $errorMessage = "[updateUserProfileContactStatus] No Cache/Core user details found for $psoftId";
                $this->log($errorMessage);
                throw new ResourceNotFoundException($errorMessage);
            }
        }
    }
}
