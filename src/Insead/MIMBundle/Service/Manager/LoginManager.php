<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;

use Insead\MIMBundle\Entity\Administrator;
use Insead\MIMBundle\Entity\ArchivedUserToken;
use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Entity\UserDevice;
use Insead\MIMBundle\Entity\UserProfileCache;
use Insead\MIMBundle\Entity\UserToken;

use Aws\Ses\SesClient;

use Insead\MIMBundle\Exception\ForbiddenException;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\StudyNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use Insead\MIMBundle\Service\BoxManager\BoxManager;
use Insead\MIMBundle\Service\StudyCourseBackup as Backup;

use Insead\MIMBundle\Exception\PermissionDeniedException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Insead\MIMBundle\Service\Redis\AuthToken as Redis;

class LoginManager extends Base
{
    protected $superAdminList;
    protected $env;
    protected $fromEmail;
    protected $ccEmail;

    protected $redis;

    /**
     * @var String AWS SES Client variable
     */
    private $sesClient;

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "User";

    private static $BEARER_HEADER   = 'Bearer';

    public function loadServiceManager(Redis $redis, $config )
    {
        $this->superAdminList = $config["study_super"];
        $this->env = $config["symfony_environment"];
        $this->fromEmail = $config["aws_ses_from_email"];
        $this->ccEmail = $config["aws_ses_review_cc_email"];

        $this->redis = $redis;

        try {
            if( isset($config["symfony_environment"]) && $config["symfony_environment"] == 'dev' ) {
                // Instantiate the SES client with AWS credentials
                $this->sesClient = new SesClient(['version' => 'latest', 'credentials' => ['key'    => $config['aws_access_key_id'], 'secret' => $config['aws_secret_key']], 'region' => $config['aws_region']]);

                $this->logger->info("Created SES Client successfully. With credentials");
            } else {
                // Instantiate the SES client without AWS credentials
                $this->sesClient = new SesClient(['version' => 'latest', 'region' => $config['aws_region']]);
            }

        } catch (Exception) {
            $this->logger->info("Unable to instantiate SES Client.");
        }
    }

    /**
     * Function to refresh a user token
     *
     * @param Request       $request            Request Object
     *
     * @throws Exception
     *
     * @return array
     */
    public function refreshLogin(Request $request)
    {
        $refreshToken = $request->get('token');

        //set log uuid as the provided refresh token
        $this->logUuid = "[" . substr((string) $refreshToken,0,8) ."..." . substr((string) $refreshToken,-8) ."]";

        /** @var UserToken $userToken */
        $userToken = $this->entityManager
            ->getRepository(UserToken::class)
            ->findOneBy(['refresh_token' => $refreshToken]);

        //determine the validity of this token based on creation date + 7days
        $interval = new \DateInterval('P7D');
        $validUntil = new \DateTime( $userToken->getCreated()->format('Ymd H:i:s') );
        $validUntil->add( $interval );

        $now = new \DateTime();

        if( $now > $validUntil ) {
            $this->log("Trying to refresh token that is only valid until: " . $validUntil->format('Ymd H:i:s'));
            throw new ForbiddenException('UserToken has already expired');
        }

        $this->redis->clearUserInfo($userToken->getOauthAccessToken());

        $token = null;

        // refresh renew token here
        if( !isset($token['expires_in']) ){
            $this->log('UserToken not found');
            throw new ForbiddenException('UserToken not found');
        }

        //calculate Token Expiry time
        $token_expiry = new \DateTime();
        $token_expiry->add(new \DateInterval('PT'.$token['expires_in'].'S'));

        //Set it in response
        $token['token_expiry'] = $token_expiry;

        //Update User Tokens
        /** @var UserToken $userToken */
        $userToken = $this->updateUserTokens($token, $refreshToken);
        $token['access_token'] = $userToken->getOauthAccessToken();

        $user = $userToken->getUser();
        $scope = $userToken->getScope();

        if( $user ) {
            //save user peoplesoftid if admin
            if( $scope == 'studysuper' || $scope == 'studyadmin' ) {

                $admin = $this->entityManager
                    ->getRepository(Administrator::class)
                    ->findOneBy(['peoplesoft_id' => $user->getPeoplesoftId()]);

                $admin->setLastLogin( new \DateTime() );

                $em = $this->entityManager;
                $em->persist($admin);
                $em->flush();
            }
        }

        $token['refresh_expiry'] = $validUntil;

        // Remove redundant properties
        unset($token['token_type']);
        unset($token['expires_in']);

        return $token;
    }

    /**
     * Function to logout a user
     *
     * @param Request       $request            Request Object
     *
     * @throws Exception
     *
     * @return Response
     */
    public function deauthenticateLogin(Request $request)
    {
        $token         = $request->get('token');
        $ios_device_id = $request->get('ios_device_id');

        //set log uuid as the provided token
        $this->logUuid = "[" . substr((string) $token,0,8) ."..." . substr((string) $token,-8) ."]";

        $userToken = $this->entityManager
            ->getRepository(UserToken::class)
            ->findOneBy(['oauth_access_token' => $token]);

        //if token if not found as an access token, try refresh token
        if( !$userToken ) {
            $this->log( "Try to look for the token as refresh token" );
            $userToken = $this->entityManager
                ->getRepository(UserToken::class)
                ->findOneBy(['refresh_token' => $token]);
        }

        if(!$userToken) {
            $this->log('UserToken not found');
            throw new ResourceNotFoundException('UserToken not found');
        }

        $em = $this->entityManager;

        if ($userToken) {
            //archive the user token information
            $withRefreshToken = $userToken->getRefreshToken() != "";

            $archivedUserToken = new ArchivedUserToken();
            $archivedUserToken->setUser( $userToken->getUser() )
                ->setScope( $userToken->getScope() )
                ->setRefreshable( $withRefreshToken )
                ->setCreated( $userToken->getUpdated() )
                ->setUpdatedValue();

            $em->persist($archivedUserToken);

            //Delete UserToken
            $em->remove($userToken);
        }

        if ($ios_device_id) {
            $userDevice = $this->entityManager
                ->getRepository(UserDevice::class)
                ->findOneBy(['ios_device_id' => $ios_device_id]);

            if ( $userDevice ) {
                //Delete UserDevice
                $em->remove($userDevice);
            }
        }

        $em->flush();

        $this->redis->clearUserInfo($token);

        $response = new Response();
        $response->setStatusCode(204);

        return $response;
    }

    /**
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function deAuthorizeBySessionIndex($sessionIndex){
        /** @var UserToken $userTokenObj */
        $userTokenObj = $this->entityManager
            ->getRepository(UserToken::class)
            ->findOneBy(['session_index' => $sessionIndex]);

        $this->log("De-authorize SessionIndex: $sessionIndex");
        if($userTokenObj){
            $this->log('ADFS Logout: Removing token: ...'.substr($userTokenObj->getAccessToken(), -7).' for user: '.$userTokenObj->getUser()->getPeoplesoftId());
            $this->log('Removing the rest of the log ins for user: ' . $userTokenObj->getUser()->getId());

            /** @var array $userTokens */
            $userTokens = $this->entityManager
                ->getRepository(UserToken::class)
                ->findBy(['user' => $userTokenObj->getUser()]);

            /** @var UserToken $userToken **/
            foreach ($userTokens as $userToken){
                //archive the user token information
                $withRefreshToken = $userToken->getRefreshToken() != "";

                $archivedUserToken = new ArchivedUserToken();
                $archivedUserToken->setUser( $userToken->getUser() )
                    ->setScope( $userToken->getScope() )
                    ->setRefreshable( $withRefreshToken )
                    ->setCreated( $userToken->getUpdated() )
                    ->setUpdatedValue();

                $this->entityManager->remove($userToken);
                $this->redis->clearUserInfo($userToken->getOauthAccessToken());
                $this->log('ADFS Logout: Removing token: ...'.substr($userToken->getAccessToken(), -7).' for user: '.$userToken->getUser()->getPeoplesoftId());
            }
            $this->entityManager->flush();
        } else {
            $this->log('Could not find SessionIndex: '.$sessionIndex);
        }
    }

    /**
     *  Function to match scope with User's role in userProfile
     *  A user logging in as 'studyadmin' should have either 'Staff' or 'Faculty' roles in userProfile
     *  A user logging in as 'studystudent' should have either 'Student' or 'Alumni' roles in userProfile
     *
     * @param   array        $peopleSoftID       user's peopleSoftID
     * @param   String       $scope              scope of the user logging in to the system
     *
     * @throws PermissionDeniedException
     */
    public function matchScopeWithUserRole($peopleSoftID, $scope)
    {
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peopleSoftID]);

        if (!$user){
            $this->log('PsoftID: '.$peopleSoftID.' do not have a user profile yet. Trying to access the application.');
            throw new PermissionDeniedException('You do not have required permission to access the app');
        }

        /** @var UserProfileCache $cacheProfile */
        $cacheProfile = $user->getUserProfileCache();

        if (!$cacheProfile){
            $this->log('PsoftID: '.$peopleSoftID.' do not have cache profile yet. Trying to access the application.');
            throw new PermissionDeniedException('You do not have required permission to access the app');
        }

        if (!$cacheProfile->getConstituentTypes()){
            $this->log('PsoftID: '.$peopleSoftID.' has no constituent type. Trying to access the application.');
            throw new PermissionDeniedException('You do not have permission to access the app');
        }

        $userConstituentTypes = array_map('intval',array_map('trim', explode(",",$cacheProfile->getConstituentTypes())));

        if( $scope == 'studysuper' ) {
            $this->log("Checking if user is an admin...");
            //Check of constituent_type is either 'Staff'/'Faculty'
            foreach($userConstituentTypes as $userConstituentType) {
                if(in_array($userConstituentType, self::$MIM_ADMIN_ROLES_ID)) {
                    $this->log("STAFF/FACULTY ROlE FOUND for user!!");
                    return;
                }
            }

            $this->log('PsoftID: '.$peopleSoftID.' is trying to access Study Admin but permission denied. Users constituent types: '.json_encode($userConstituentTypes));
            throw new PermissionDeniedException('You do not have required permissions or you are using the wrong scope.');
        } elseif ($scope == 'studyadmin' || $scope == 'studysupport') {
            // Check of constituent_type is a 'Faculty'
            // If Faculty it will be auto allow
            foreach($userConstituentTypes as $userConstituentType) {
                if( $userConstituentType == self::$FACULTY_ROLE ) {
                    $this->log("FACULTY ROlE FOUND for PsoftID: $peopleSoftID. Allowing the access.");
                    return;
                }
            }

            $this->log("A scope: $scope is trying to log in. Validating if they have access");

            /** @var Administrator $admin */
            $admin = $this->entityManager
                ->getRepository(Administrator::class)
                ->findOneBy(['peoplesoft_id' => $peopleSoftID]);

            if ($admin) {
                if ($admin->getBlocked()){
                    $this->log("The peopleSoftID: $peopleSoftID is blocked from logging in");
                    throw new PermissionDeniedException('Your account is currently blocked from logging in.');
                }
            } else {
                $this->log("User is not included in the Admin table.");
                throw new PermissionDeniedException('You do not have permission to access the app.');
            }

        } elseif($scope == '' || $scope === null) {
            $this->log("No scope defined.");
            throw new PermissionDeniedException('You do not have permission to access the app');
        }
    }

    public function isSuperScope($scope, $peopleSoftID){
        $superAdmins = [];
        if( $this->superAdminList && ($scope == "studyadmin" || $scope == "studysuper")) {
            $superAdmins = explode(",",(string) $this->superAdminList);

            if( count($superAdmins) ) {
                //if the user's peoplesoftid is in the super admin list, change the scope to super admin
                if( array_search($peopleSoftID,$superAdmins) !== false ) {
                    $scope = "studysuper";
                } else {
                    if( $scope == "studysuper" ) {
                        $scope = "studyadmin";
                    }
                }
            }

            //if it is still a normal admin, check if they should only have support access
            if( $scope == "studyadmin" ) {
                $admin = $this->entityManager
                    ->getRepository(Administrator::class)
                    ->findOneBy(['peoplesoft_id' => $peopleSoftID]);

                if ($admin) {
                    if ($admin->getSupportOnly()) {
                        $scope = "studysupport";
                    }
                }
            }
        }

        return $scope;
    }


    /**
     *  Function to create user with peopleSoft id
     *  checks if user with peopleSoft id exists, if not, creates one
     * @param array $response array object containing the response from authorization server
     * @param String $deviceId device id of iPad
     * @param String $scope scope of access that the user currently has
     *
     * @return UserToken
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidResourceException
     */
    public function persistUserTokens($response, $deviceId, $scope)
    {
        $userToken = null;

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $response['peoplesoft_id']]);

        if($user) {
            $date = new \dateTime();

            $generatedToken = $this->generateHashedToken(
                $response['access_token'] . $date->format('Y-m-d H:i:s'),
                $response['peoplesoft_id']
            );

            $user->setIosDeviceId($deviceId);
            $this->updateRecord(self::$ENTITY_NAME, $user);

            $userToken = new UserToken();
            $userToken->setAccessToken($response['access_token']);
            $userToken->setOauthAccessToken( $generatedToken );
            $userToken->setRefreshToken($response['refresh_token']);
            $userToken->setTokenExpiry($response['token_expiry']);
            $userToken->setSessionIndex($response['session_index']);
            $userToken->setScope($scope);
            $userToken->setUser($user);
            $this->createRecord('UserToken', $userToken);

        } else {

            $date = new \dateTime();
            $user = new User();

            $generatedToken = $this->generateHashedToken(
                $response['access_token'] . $date->format('Y-m-d H:i:s'),
                $response['peoplesoft_id']
            );

            $user->setPeoplesoftId($response['peoplesoft_id']);
            $user->setIosDeviceId($deviceId);
            $user->setAgreement(false);

            $this->createRecord(self::$ENTITY_NAME, $user);

            $userToken = new UserToken();
            $userToken->setAccessToken($response['access_token']);
            $userToken->setOauthAccessToken( $generatedToken );
            $userToken->setRefreshToken($response['refresh_token']);
            $userToken->setTokenExpiry($response['token_expiry']);
            $userToken->setSessionIndex($response['session_index']);
            $userToken->setScope($scope);
            $userToken->setUser($user);
            $this->createRecord('UserToken', $userToken);
        }

        return $userToken;
    }

    /**
     * Function to add the device into the list of iOS Notification
     *
     * @throws ResourceNotFoundException
     */
    public function addToiOSNotification(Request $request, string $deviceToken){
        /** @var User $user */
        $user = $this->getCurrentUser($request);

        if ($user){
            $user->setIosDeviceId($deviceToken);
            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush($user);
            } catch (ORMException) {
                $this->log("Unable to persis user[".$user->getPeoplesoftId()."] for iOS Notification");
            }
        } else {
            $this->log("Unable to find User for iOS Notification");
        }
    }

    /**
     * Function to remove the device into the list of iOS Notification
     *
     * @throws ResourceNotFoundException
     */
    public function removeToiOSNotification(Request $request, string $deviceToken){
        /** @var User $user */
        $user = $this->getCurrentUser($request);

        if ($user){
            $user->unsetIosDeviceId($deviceToken);
            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush($user);
            } catch (ORMException) {
                $this->log("Unable to persis user[".$user->getPeoplesoftId()."] for iOS Notification");
            }
        } else {
            $this->log("Unable to find User for iOS Notification");
        }
    }

    /**
     *  Function to create a Temporary Service Token for user
     *  checks if user's Token is valid
     * @param String $accessToken Access Token used by the requestor
     *
     * @return UserToken
     */
    public function generateServiceToken($accessToken)
    {
        if($this->startsWith($accessToken, self::$BEARER_HEADER)) {
            $accessToken = trim(substr($accessToken, strlen((string) self::$BEARER_HEADER), strlen($accessToken)));
        }

        $newUserToken = null;
        $now = new \DateTime();

        //valid until next 1hr
        $interval = new \DateInterval('PT1H');
        $expiry = new \DateTime();
        $expiry->add($interval);

        /** @var UserToken $userToken */
        $userToken = $this->entityManager
            ->getRepository(UserToken::class)
            ->findOneBy(['oauth_access_token' => $accessToken]);

        if($userToken) {
            $this->log('UserToken found... generating service token');
            $user = $userToken->getUser();
            $scope = $userToken->getScope();

            $serviceAccessToken = $userToken->getAccessToken();

            $generatedToken = $this->generateHashedToken(
                $serviceAccessToken . $now->format('Y-m-d H:i:s'),
                $userToken->getUser()->getPeoplesoftId()
            );

            //new service token for the requestor which is valid for the next 1hr
            $newUserToken = new UserToken();
            $newUserToken->setAccessToken( $serviceAccessToken );
            $newUserToken->setOauthAccessToken( $generatedToken );
            $newUserToken->setRefreshToken('');
            $newUserToken->setTokenExpiry($expiry);
            $newUserToken->setUser($user);

            if( $scope == "studysuper" ) {
                $newUserToken->setScope('studyssvc');
            } else {
                $newUserToken->setScope('studysvc');
            }

            try {
                $this->createRecord('UserToken', $newUserToken);
            } catch (OptimisticLockException $e) {
                $this->log("OptimisticLockException Error: ".$e->getMessage());
            } catch (ORMException $e) {
                $this->log("ORMException Error: ".$e->getMessage());
            } catch (InvalidResourceException $e) {
                $this->log("InvalidResourceException Error: ".$e->getMessage());
            }

        }

        return $newUserToken;
    }

    public function generatePeoplesoftIdHash($psoftId)
    {
        $preHash = hash_hmac('sha256',
            (string) $psoftId,
            'cdc57d74dccbfceba4a4103fddbbd8794c5c003f1655771a3b076efa2f98b887',
            false
        );

        return substr($preHash, 0, 7);
    }

    private function sendReviewEmailFor($person)
    {
        $msg = [];
        $msg['Source'] = $this->fromEmail;

        $env            = $this->env;
        $ccList         = $this->ccEmail;

        $msg['Destination']['ToAddresses'][]        = $this->fromEmail;

        if( $ccList ) {
            $msg['Destination']['CcAddresses'] = explode(",", (string) $ccList);
        }

        //ToAddresses must be an array; if environment is prd or prd2, use the real email, otherwise send to sender
        if( str_contains((string) $env,'prd') ) {
            $msg['Message']['Subject']['Data']          = "Pending Study administrator access";
        } else {
            $msg['Message']['Subject']['Data']          = "Pending " . strtoupper((string) $env) . " Study administrator access";
        }
        $msg['Destination']['BccAddresses'][] = "appdev.testing@insead.edu";

        $msg['Message']['Subject']['Charset'] = "UTF-8";

        $htmlContent        = "";
        $htmlContent        = $htmlContent . "Dear All, ";
        $htmlContent        = $htmlContent . "<br/><br/>" . $person . " is requesting access to Study@INSEAD admin. ";
        $htmlContent        = $htmlContent . "<br/>Please check the Manage Administrators page to process the request. ";
        $htmlContent        = $htmlContent . "<br/><br/>Best regards, ";
        $htmlContent        = $htmlContent . "<br/>INSEAD Study Team";

        $rawContent = $htmlContent;
        $rawContent = str_replace("<br/>","",$rawContent);

        $msg['Message']['Body']['Text']['Data'] =  $rawContent;
        $msg['Message']['Body']['Text']['Charset'] = "UTF-8";
        $msg['Message']['Body']['Html']['Data'] = $htmlContent;
        $msg['Message']['Body']['Html']['Charset'] = "UTF-8";

        try{
            $result = $this->sesClient->sendEmail($msg);

            //save the MessageId which can be used to track the request
            $msg_id = $result->get('MessageId');
            $this->logger->info("Email notification sent : " .
                json_encode(["msg_id" => $msg_id])
            );

        } catch (Exception $e) {
            //An error occurred and the email did not get sent
            $this->logger->error('ERROR MESSAGE:: ' . $e->getMessage());
        }
    }

    /**
     *  Function to check response from OAuth Authenticate api
     *  Checks if 'access-token', 'refresh_token' & 'expires_in' fields are in the response
     *
     * @param   array    $item       array object of the response
     *
     * @throws PermissionDeniedException
     */
    private function checkAuthUserResponse($item)
    {
        if($item !== null) {
            if (array_key_exists('access_token', $item) &&
                array_key_exists('refresh_token', $item) &&
                array_key_exists('expires_in', $item)) {
                return;
            } else {
                $this->log("Error occurred when trying to communicate with AS: " . json_encode($item));

                throw new PermissionDeniedException();
            }
        }
    }

    private function generateHashedToken($msg,$psoftId)
    {
        $token = hash_hmac('sha256',
            (string) $msg,
            'cdc57d74dccbfceba4a4103fddbbd8794c5c003f1655771a3b076efa2f98b887',
            false
        );

        if( !is_null($psoftId) && $psoftId != "" ) {
            $preHash = $this->generatePeoplesoftIdHash($psoftId);

            $token = substr((string) $preHash, 0, 7) . "0" . $token;
        }

        return $token;
    }

    /**
     *  Function to update user with refresh token
     *  checks if user with peoplesoft id exists, if not, creates one
     *
     * @param Response $response Response object passed
     * @param String $refreshToken Refresh Token of the user
     *
     * @return String
     * @throws ForbiddenException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PermissionDeniedException
     * @throws ResourceNotFoundException
     */
    private function updateUserTokens($response, $refreshToken)
    {
        $superAdmins = [];
        if( $this->superAdminList ) {
            $superAdmins = explode(",",(string) $this->superAdminList);
        }

        /** @var UserToken $userToken */
        $userToken = $this->entityManager
            ->getRepository(UserToken::class)
            ->findOneBy(['refresh_token' => $refreshToken]);

        if(!$userToken) {
            $this->log('UserToken not found');
            throw new ResourceNotFoundException('UserToken not found');
        }

        $user = $userToken->getUser();
        $scope = $userToken->getScope();

        //check if user is administrator
        if( $scope == "studyadmin" || $scope == "studysuper" ) {
            if( count($superAdmins) ) {
                //if the user's peoplesoftid is in the super admin list, change the scope to super admin
                if( array_search($user->getPeoplesoftId(),$superAdmins) !== false ) {
                    $scope = "studysuper";
                } else {
                    $scope = "studyadmin";
                }
            }

            $admin = $this->entityManager
                ->getRepository(Administrator::class)
                ->findOneBy(['peoplesoft_id' => $user->getPeoplesoftId()]);

            if( !$admin ) {
                throw new ForbiddenException('UserToken not found');
            }

            if( $admin->getBlocked() ) {
                throw new PermissionDeniedException('Your access is pending for review, Study team administrators will contact you once access is granted.');
            }
        }

        //archive the user token information before refreshing the token
        $withRefreshToken = $userToken->getRefreshToken() != "";

        $archivedUserToken = new ArchivedUserToken();
        $archivedUserToken->setUser( $userToken->getUser() )
            ->setScope( $userToken->getScope() )
            ->setRefreshable( $withRefreshToken )
            ->setCreated( $userToken->getUpdated() )
            ->setUpdatedValue();

        $em = $this->entityManager;
        $em->persist($archivedUserToken);

        //refresh the token
        $date = new \dateTime();

        $generatedToken = $this->generateHashedToken(
            $response['access_token'] . $date->format('Y-m-d H:i:s'),
            $user->getPeoplesoftId()
        );

        $userToken->setAccessToken($response['access_token']);
        $userToken->setOauthAccessToken( $generatedToken );
        $userToken->setRefreshToken($response['refresh_token']);
        $userToken->setTokenExpiry($response['token_expiry']);
        $userToken->setScope($scope);
        $this->updateRecord('UserToken', $userToken);

        return $userToken;
    }

    //returns true if $haystack starts with $needle
    private function startsWith($haystack, $needle)
    {
        $length = strlen((string) $needle);
        return (substr((string) $haystack, 0, $length) === $needle);
    }

}
