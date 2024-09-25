<?php

namespace esuite\MIMBundle\Service\Manager;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use esuite\MIMBundle\Entity\Administrator;
use esuite\MIMBundle\Entity\BarcoUser;
use esuite\MIMBundle\Entity\ProgrammeAdministrator;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\UserProfileCache;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\PermissionDeniedException;
use esuite\MIMBundle\Service\AIPService;
use phpDocumentor\Reflection\Types\Boolean;
use esuite\MIMBundle\Service\Barco\User as BarcoUserService;

use esuite\MIMBundle\Exception\ResourceNotFoundException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request;


class UserCheckerManager extends Base
{
    protected $login;
    protected $adwsUrl;
    protected $adwsUsername;
    protected $adwsPassword;
    protected $superAdminList;
    protected $userProfileManager;
    protected $barcoUserService;

    protected $AIPService;

    public function loadServiceManager(LoginManager $login, $adwsConfig, $config, AIPService $AIPService, UserProfileManager $userProfileManager, BarcoUserService $barcoUserService)
    {
        $this->login = $login;

        $this->adwsUrl          = $adwsConfig["adws_url"];
        $this->adwsUsername     = $adwsConfig["adws_username"];
        $this->adwsPassword     = $adwsConfig["adws_password"];
        $this->superAdminList   = $config["edot_super"];
        $this->AIPService       = $AIPService;
        $this->barcoUserService = $barcoUserService;

        /** @var UserProfileManager userProfileManager */
        $this->userProfileManager = $userProfileManager;
    }

    /**
     * Function to find user information
     *
     * @param $criterion
     *
     * @return array
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function checkUserInfo(Request $request, $criterion)
    {
        $returnAll = !$request->query->has('agreementOnly');
        $aswsResult = [];
        $subscriptions = [];
        $programmes = [];
        $courses = [];
        $sessionHosts = [];
        $programmeAdmins = [];
        $cacheUserProfile = false;

        $this->log("Get Profile information of user with criterion: " . $criterion);

        $em = $this->entityManager;

        //user entity
        /** @var User $user */
        $user = $em->getRepository(User::class)
            ->findOneBy(["peoplesoft_id"=>$criterion]);

        if ($user) {
            /** @var UserProfileCache $cacheUserProfile */
            $cacheUserProfile = $user->getCacheProfile();
        }

        //adwebservices
        $adWebserviceResult = $this->findUserInAdWebservice( $criterion,'esuitePsoftId' );

        if ($adWebserviceResult) {
            //cleanup xml result to appear as XML data before parsing
            $adWebserviceResult = str_replace("a:","",$adWebserviceResult);
            $adWebserviceResult = str_replace("s:","",$adWebserviceResult);
            $adWebserviceResult = str_replace("i:nil=\"true\"","",$adWebserviceResult);
            $adWebserviceObj = simplexml_load_string($adWebserviceResult);

            $adwsCount = (string)$adWebserviceObj->Body->FindUsersResponse->FindUsersResult->UserCount;

            if ($adwsCount) {
                $aswsResult = [
                    "count" => $adwsCount,
                    "upn" => (string)$adWebserviceObj->Body->FindUsersResponse->FindUsersResult->Result->UserProperties->UserPrincipalName,
                    "peoplesoftid" => (string)$adWebserviceObj->Body->FindUsersResponse->FindUsersResult->Result->UserProperties->PsoftID
                ];

                if ($returnAll) {
                    $aswsResult["esuite_object_type"] = (string)$adWebserviceObj->Body->FindUsersResponse->FindUsersResult->Result->UserProperties->esuiteObjectType;
                }
            }
        }

        //loguuid
        $logUUID = $this->login->generatePeoplesoftIdHash($criterion);

        //avatarKey
        $avatarUUID = md5((string) $criterion);

        if( $returnAll ) {
            //course subscription entity
            $courseSubscriptions = $em->getRepository(CourseSubscription::class)
                ->findBy(["user"=>$user]);

            $cachedUserList = [];
            /** @var CourseSubscription $item */
            foreach($courseSubscriptions as $item) {

                $item->getProgramme()->setOverriderReadonly(true);
                if( !isset($subscriptions[ $item->getProgramme()->getId() ]) ) {
                    $subscriptions[ $item->getProgramme()->getId() ] = [];
                }

                if( !isset($subscriptions[ $item->getProgramme()->getId() ][ $item->getCourse()->getId() ] ) ) {
                    $subscriptions[ $item->getProgramme()->getId() ][ $item->getCourse()->getId() ] = [];
                }

                /** @var ProgrammeAdministrator $programmeOwner */
                $programmeOwner = $em->getRepository(ProgrammeAdministrator::class)
                    ->findOneBy(["programme" => $item->getProgramme(), "owner" => true]);

                $myOwner = [];
                if ($programmeOwner){
                    if (!array_key_exists($programmeOwner->getUser()->getPeoplesoftId(), $cachedUserList)){

                        /** @var UserProfileCache $cacheProfile */
                        $cacheProfile = $programmeOwner->getUser()->getCacheProfile();
                        if ($cacheProfile){
                            $UPN = $cacheProfile->getUpnEmail();
                        } else {
                            $UPN = "";
                        }

                        $cachedUserList[$programmeOwner->getUser()->getPeoplesoftId()] = ["name" => $cacheProfile->getFirstname()." ".$cacheProfile->getLastname(), "upn" => $UPN];
                    }
                    $myOwner[] = ["name" => $cachedUserList[$programmeOwner->getUser()->getPeoplesoftId()]['name'], "upn" => $cachedUserList[$programmeOwner->getUser()->getPeoplesoftId()]['upn']];
                } else {
                    $coordinationList = $item->getCourse()->getCoordination(true);
                    if ($coordinationList){
                        /** @var User $userOwner */
                        foreach ($coordinationList as $userOwner){
                            if (!array_key_exists($userOwner->getPeoplesoftId(), $cachedUserList)){
                                if ($cacheUserProfile) {
                                    /** @var UserProfileCache $cacheProfile */
                                    $cacheProfile = $userOwner->getCacheProfile();
                                    if ($cacheProfile){
                                        $UPN = $cacheProfile->getUpnEmail();
                                    } else {
                                        $UPN = "";
                                    }

                                    $cachedUserList[$userOwner->getPeoplesoftId()] = ["name" => $userOwner->getFirstname()." ".$userOwner->getLastname(), "upn" => $UPN];
                                }
                            }

                            array_push($myOwner,["name" => $cachedUserList[$userOwner->getPeoplesoftId()]['name'], "upn" => $cachedUserList[$userOwner->getPeoplesoftId()]['upn']]
                            );
                        }
                    } else {
                        $esuiteTeamList = $item->getCourse()->getesuiteTeam(true);
                        if ($esuiteTeamList){
                            /** @var User $userOwner */
                            foreach ($esuiteTeamList as $userOwner){
                                if (!$cachedUserList[$userOwner->getPeoplesoftId()]){
                                    $UPN = null;
                                    $first_name = null;
                                    $last_name = null;

                                    /** @var UserProfileCache $cacheProfile */
                                    $cacheProfile = $userOwner->getCacheProfile();
                                    if ($cacheProfile){
                                        $UPN = $cacheProfile->getUpnEmail();
                                    } else {
                                        $UPN = "";
                                    }

                                    $first_name = $cacheProfile->getFirstname();
                                    $last_name = $cacheProfile->getLastname();

                                    $cachedUserList[$userOwner->getPeoplesoftId()] = ["name" => $first_name." ".$last_name, "upn" => $UPN];
                                }

                                $myOwner[] = ["name" => $cachedUserList[$userOwner->getPeoplesoftId()]['name'], "upn" => $cachedUserList[$userOwner->getPeoplesoftId()]['upn']];
                            }
                        }
                    }
                }

                $subscription = ["id" => $item->getId(), "programme" => $item->getProgramme()->getName(), "course" => $item->getCourse()->getName(), "role" => $item->getRole()->getName(), "owner" => $myOwner, "start_date" => $item->getCourse()->getStartDate(), "timezone" => $item->getCourse()->getTimezone()];

                $subscriptions[$item->getProgramme()->getId()][$item->getCourse()->getId()][] = $subscription;
            }

            //programmes
            /** @var CourseSubscription $item */
            foreach($courseSubscriptions as $item) {
                if( !isset($programmes[ $item->getProgramme()->getId() ]) ) {
                    $programmes[ $item->getProgramme()->getId() ] = ["id" => $item->getProgramme()->getId(), "name" => $item->getProgramme()->getName(), "code" => $item->getProgramme()->getCode()];
                }
            }

            //course
            /** @var CourseSubscription $item */
            foreach($courseSubscriptions as $item) {
                if( !isset($courses[ $item->getCourse()->getId() ] ) ) {
                    $courses[ $item->getCourse()->getId() ] = ["id" => $item->getCourse()->getId(), "name" => $item->getCourse()->getName(), "abbreviation" => $item->getCourse()->getAbbreviation()];
                }
            }

            //as session host
            if( $user ) {
                /** @var Session $session */
                foreach($user->getUserSessions() as $session) {
                    if( !isset($sessionHosts[ $session->getCourse()->getProgramme()->getId() ]) ) {
                        $sessionHosts[ $session->getCourse()->getProgramme()->getId() ] = [];
                    }

                    if( !isset($sessionHosts[ $session->getCourse()->getProgramme()->getId() ][ $session->getCourse()->getId() ] ) ) {
                        $sessionHosts[ $session->getCourse()->getProgramme()->getId() ][ $session->getCourse()->getId() ] = [];
                    }

                    $host = ["programme" => $session->getCourse()->getProgramme()->getName(), "programme_id" => $session->getCourse()->getProgramme()->getId(), "course" => $session->getCourse()->getName(), "course_id" => $session->getCourse()->getId(), "session" => $session->getName(), "id" => $session->getId()];

                    $sessionHosts[$session->getCourse()->getProgramme()->getId()][$session->getCourse()->getId()][] = $host;
                }

            }

            //as super admin
            if( $user ) {
                $programmeAdmins["super"] = false;
                $programmeAdmins["admin"] = false;
                $programmeAdmins["programmes"] = [];

                $superAdmins = [];
                if( $this->superAdminList ) {
                    $superAdmins = explode(",",(string) $this->superAdminList);
                }

                if( count($superAdmins) ) {
                    //if the user's peoplesoftid is in the super admin list, change the scope to super admin
                    if( array_search($user->getPeoplesoftId(),$superAdmins) !== false ) {
                        $programmeAdmins["super"] = true;
                    }
                }

                /** @var ProgrammeAdministrator $programmeAdmin */
                $programmeAdmin = $em->getRepository(ProgrammeAdministrator::class)
                    ->findBy(["user" => $user->getId()]);

                /** @var ProgrammeAdministrator $pAdmin */
                foreach( $programmeAdmin as $pAdmin) {
                    $programmeAdmins["programmes"][] = ["programme" => $pAdmin->getProgramme(), "is_owner" => $pAdmin->getOwner()];
                }
            }

            //check if faculty
            /** @var Administrator $admin */
            $admin = $em->getRepository(Administrator::class)
                ->findOneBy(["peoplesoft_id" => $criterion]);

            if( $admin ) {
                if( !$admin->getBlocked() ) {
                    $programmeAdmins["admin"] = true;
                }

                $isFaculty = false;

                $usersConstituentTypeString = $user->getUserConstituentTypeString();
                foreach ($usersConstituentTypeString as $cType) {
                    if ($cType['constituent_type'] === "Faculty") {
                        $isFaculty = true;
                        break;
                    }
                }

                if( $admin->getFaculty() != $isFaculty ) {
                    $admin->setFaculty($isFaculty);

                    $em->persist($admin);
                    $em->flush();
                }
            }
        }

        // check for barco user
        $checkBarcoUser = false;
        if ( $user ){
            $barcoUser = $this->entityManager->createQueryBuilder()->select('b')
                ->from(BarcoUser::class, 'b')
                ->where('b.peoplesoft_id= :peoplesoft_id')
                ->setParameter('peoplesoft_id',$user->getPeoplesoftId())
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);

            if ( $barcoUser ){
                $checkBarcoUser = $barcoUser[0];
                try {
                    $checkBarcoUser['barcoUsergroupDetails'] = $this->barcoUserService->getUsergroupsById($checkBarcoUser['barcoUserId']);
                } catch (InvalidResourceException){
                    $checkBarcoUser['barcoUsergroupDetails'] = false;
                }

                try {
                    $checkBarcoUser['barcoUserDetails'] = $this->barcoUserService->getUserById($checkBarcoUser['barcoUserId']);
                } catch (InvalidResourceException){
                    $checkBarcoUser['barcoUserdetails'] = false;
                }
            }
        }

        //-------------------------

        $userInfo = ["criterion" => $criterion, "loguuid" => $logUUID, "avatar_uuid" => $avatarUUID];

        if( $aswsResult ) {
            $userInfo["adwebservice"] = $aswsResult;
        }

        if( $returnAll ) {
            if( $subscriptions ) {
                $userInfo["subscriptions"] = $subscriptions;
            }

            if( $programmes ) {
                $userInfo["programmes"] = $programmes;
            }

            if( $courses ) {
                $userInfo["courses"] = $courses;
            }

            if( $sessionHosts ) {
                $userInfo["sessions"] = $sessionHosts;
            }

            if( $programmeAdmins ) {
                $userInfo["programmeadmins"] = $programmeAdmins;
            }
        }

        $userInfo["golive"] = $checkBarcoUser;


        if( $user ) {
            $userInfo["user"] = ["box_user_id" => $user->getBoxId(), "box_user_email" => $user->getBoxEmail(), "agreement" => $user->getAgreement(), "agreement_at" => $user->getAgreementDate(), "last_login_at" => $user->getLastLoginDate(), "last_profile_update" => $user->getProfileLastUpdated()];

            try {
                $avatarObj = $this->userProfileManager->getAvatarS3Obj($user->getPeoplesoftId());
                $avatar = "data:image/png;base64," . base64_encode((string) $avatarObj['Body']);
            } catch (\Exception){
                $this->log("No profile picture found for PeopleSoftID: ".$user->getPeoplesoftId());
                $avatar = "-";
            }

            if ($user->getCoreProfile()){
                $userInfo["edot_profile"] = ["reference" => "Core", "details" => $user->getCoreProfile(), "avatar" => $avatar, "isAllowedToUpdate" => $user->getAllowedToUpdate(), "isCore" => true];
            } else {
                if ($user->getCacheProfile()){
                    $userInfo["edot_profile"] = ["reference" => "Cache", "details" => $user->getCacheProfile(), "avatar" => $avatar, "isAllowedToUpdate" => $user->getAllowedToUpdate(), "isCore" => false];
                } else {
                    $userInfo["edot_profile"] = ["reference" => "", "details" => "", "avatar" => ""];
                }
            }
        }

        $userInfo["peoplesoft_profile"] = [];

        return ['userinfo' => $userInfo];
    }

    /**
     * Function to get peopleSoft user information
     *
     * @param Request $request
     * @param $peopleSoftID
     *
     * @return array
     */
    public function getUserPSoftInfo(Request $request, $peopleSoftID)
    {

        $userInfo = ["criterion" => $peopleSoftID, "loguuid" => $this->login->generatePeoplesoftIdHash($peopleSoftID), "avatar_uuid" => md5((string) $peopleSoftID)];

        try {
            $peopleSoftDetails = $this->AIPService->getUserApi($peopleSoftID);
            $userInfo["peoplesoft_profile"] = $peopleSoftDetails;
        } catch (\Exception) {
            $userInfo["peoplesoft_profile"] = "Unable to get profile";
        }

        return ['userinfo' => $userInfo];
    }

    /**
     * Function to find user based on their AD upn
     *
     * @param Request       $request            Request Object
     * @param String        $criterion          Search criterion
     * @param Boolean       $force             force search short criterion (<5 chars)
     *
     * @return array
     */
    public function findUsersByUpn(Request $request, $criterion, $force = false)
    {

        $users = [];

        $this->log("Finding users with criterion: " . $criterion);

        $criterion = trim($criterion);

        if( (strlen($criterion) >= 3 && $force) || strlen($criterion) >= 5 ) {
            $adWebserviceResult = $this->findUserInAdWebservice( $criterion,'mail' );
            $adWebserviceObj = $this->adXmlParseAndCleanUp($adWebserviceResult);
            $adwsCount = (string) $adWebserviceObj->Body->FindUsersResponse->FindUsersResult->UserCount;

            //if there are no exact matches, check upn
            if( $adwsCount == 0 ) {
                $adWebserviceResult = $this->findUserInAdWebservice( $criterion,'userPrincipalName' );
                $adWebserviceObj = $this->adXmlParseAndCleanUp($adWebserviceResult);
                $adwsCount = (string) $adWebserviceObj->Body->FindUsersResponse->FindUsersResult->UserCount;
            }

            //if there are no exact matches, do partial if forced
            if( $adwsCount == 0 && $force ) {
                $adWebserviceResult = $this->findUserInAdWebservice( '*' . $criterion . '*','userPrincipalName' );
                $adWebserviceObj = $this->adXmlParseAndCleanUp($adWebserviceResult);
            }

            $adwsCount = (string) $adWebserviceObj->Body->FindUsersResponse->FindUsersResult->UserCount;
            $results = (array) $adWebserviceObj->Body->FindUsersResponse->FindUsersResult->Result;
            $userResults = $results["UserProperties"];

            if( $adwsCount == 1 ) {
                $temp = $this->formatADMessage($userResults);
                $users[] = $temp;
            } else if( $adwsCount > 1 ) {
                foreach ($userResults as $user) {
                    $temp = $this->formatADMessage($user);
                    $users[] = $temp;
                }
            }
        }

        return ['userlist' => $users];
    }

    /**
     * Handler to update user details by superadmin
     * @param Request $request
     * @param $peopleSoftID
     * @param $mode
     *
     * @return mixed
     * @throws InvalidResourceException
     */
    public function adminUpdateUser( Request $request, $peopleSoftID, $mode ){
        $result = match ($mode) {
            'avatar' => $this->adminUploadAvatar($request, $peopleSoftID),
            'bio' => $this->adminUpdateBio($request, $peopleSoftID),
            'prefjobTitle' => $this->adminUpdateTempJobTitle($request, $peopleSoftID),
            default => throw new InvalidResourceException(["You are not allowed to use this API endpoint."]),
        };

        return $result;
    }

    /**
     * Function to update user avatar by Admin
     *
     * @param Request $request
     * @param $peopleSoftID
     * @return mixed
     */
    private function adminUploadAvatar(Request $request, $peopleSoftID){
        try {
            $this->userProfileManager->uploadAvatarByAdmin( $request, $peopleSoftID );
            return 'success';
        } catch (\Exception){
            return 'error';
        }
    }

    /**
     * Function to update user Bio by Admin
     *
     * @param Request $request
     * @param $peopleSoftID
     * @return mixed
     */
    private function adminUpdateBio(Request $request, $peopleSoftID){
        try {
            $this->userProfileManager->updateBioByAdmin( $request, $peopleSoftID );
            return 'success';
        } catch (\Exception){
            return 'error';
        }
    }

    /**
     * Function to update user Temp Job Title by Admin
     *
     * @param Request $request
     * @param $peopleSoftID
     * @return mixed
     */
    private function adminUpdateTempJobTitle(Request $request, $peopleSoftID){
        try {
            $this->userProfileManager->updateTempJobTitleByAdmin( $request, $peopleSoftID );
            return 'success';
        } catch (\Exception){
            return 'error';
        }
    }

    /**
     * Format the message from AD Object
     * @param $ADXMLObject
     * @return string[]
     */
    private function formatADMessage($ADXMLObject){
        return ["upn" => (string)$ADXMLObject->UserPrincipalName, "first_name" => (string)$ADXMLObject->GivenName, "last_name" => (string)$ADXMLObject->SurName, "peoplesoft_id" => (string)$ADXMLObject->PsoftID, "mail" => (string)$ADXMLObject->Mail];
    }

    /**
     * Clean and Parse XML from AD
     *
     * @param $adWebserviceResultRequest
     * @return SimpleXMLElement|string
     */
    private function adXmlParseAndCleanUp($adWebserviceResultRequest){
        //cleanup xml result to appear as XML data before parsing
        $adWebserviceResult = str_replace("a:","",$adWebserviceResultRequest);
        $adWebserviceResult = str_replace("s:","",$adWebserviceResult);

        return simplexml_load_string($adWebserviceResult);
    }

    /**
     * Get userinfo from AD
     *
     * @param $criterion
     * @param string $field
     * @return bool|string
     */
    private function findUserInAdWebservice( $criterion, $field="esuitePsoftId" ) {
        $adwebserviceXML = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $adwebserviceXML = $adwebserviceXML . "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ins=\"http://schemas.datacontract.org/2004/07/esuiteADServices\" xmlns:tem=\"http://tempuri.org/\">";
            $adwebserviceXML = $adwebserviceXML . "<soapenv:Header>";
                $adwebserviceXML = $adwebserviceXML . "<Authentication>";
                    $adwebserviceXML = $adwebserviceXML . "<ins:ServicePassword>" . $this->adwsPassword . "</ins:ServicePassword>";
                    $adwebserviceXML = $adwebserviceXML . "<ins:ServiceUsername>" . $this->adwsUsername . "</ins:ServiceUsername>";
                $adwebserviceXML = $adwebserviceXML . "</Authentication>";
            $adwebserviceXML = $adwebserviceXML . "</soapenv:Header>";
            $adwebserviceXML = $adwebserviceXML . "<soapenv:Body>";
                $adwebserviceXML = $adwebserviceXML . "<tem:FindUsers>";
                    $adwebserviceXML = $adwebserviceXML . "<tem:UserProperties>";
                        $adwebserviceXML = $adwebserviceXML . "<ins:AttributesCollection>";
                            $adwebserviceXML = $adwebserviceXML . "<ins:AttributeKey>" . $field ."</ins:AttributeKey>";
                            $adwebserviceXML = $adwebserviceXML . "<ins:AttributeValue>" . $criterion . "</ins:AttributeValue>";
                        $adwebserviceXML = $adwebserviceXML . "</ins:AttributesCollection>";
                    $adwebserviceXML = $adwebserviceXML . "</tem:UserProperties>";
                $adwebserviceXML = $adwebserviceXML . "</tem:FindUsers>";
            $adwebserviceXML = $adwebserviceXML . "</soapenv:Body>";
        $adwebserviceXML = $adwebserviceXML . "</soapenv:Envelope>";

        $curl = curl_init();
        $headers_encode = ['Content-Type: text/xml', "Connection: close", "SOAPAction: http://tempuri.org/IADService/FindUsers"];

        curl_setopt($curl, CURLOPT_URL, $this->adwsUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1000);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 100);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers_encode);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_VERBOSE,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FILETIME, true);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $adwebserviceXML);

        return curl_exec($curl);
    }

    public function getADExpiryDate(Request $request, $peopleSoftID) {
        $result = [];
        try {
            
            $personDetail = $this->AIPService->getPersonDetail($peopleSoftID);
            $result['userinfo'] = $personDetail['meta_person'][0]['subsystem_fields'];
            
        } catch (\Exception $e) {
            $result['userinfo'] = $e->getMessage();
        }

        return ['userinfo' => $result['userinfo']];
    }

    /**
     * @throws \Exception
     */
    public function getPersonInfo($peopleSoftID, $type) {
        try {
            return $this->AIPService->getPersonDetailByType($peopleSoftID, $type);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }
}
