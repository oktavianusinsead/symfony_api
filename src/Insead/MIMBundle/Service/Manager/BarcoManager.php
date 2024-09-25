<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use GuzzleHttp\Exception\RequestException;
use Insead\MIMBundle\Entity\BarcoUserGroup;
use Insead\MIMBundle\Entity\UserProfileCache;
use Insead\MIMBundle\Service\AIPService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Symfony\Component\HttpFoundation\Request;
use Insead\MIMBundle\Entity\BarcoUser;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Service\Barco\UserGroups;
use Insead\MIMBundle\Service\Barco\User;

class BarcoManager extends Base
{
    /** @var User $barcoUserService */
    protected $barcoUserService;

    /** @var UserGroups $barcoUserGroupService */
    protected $barcoUserGroupService;

    /** @var UtilityManager $utilityManager */
    protected $utilityManager;

    /** @var AIPService $aipManager */
    protected $aipManager;

    /** @var UserProfileManager $userProfileManager */
    protected $userProfileManager;

    /** @var UserCheckerManager $userCheckerManager */
    protected $userCheckerManager;

    public function loadServiceManager(UtilityManager $utilityManager, AIPService $AIPService, UserProfileManager $userProfileManager, User $user, UserGroups $userGroups, UserCheckerManager $userCheckerManager)
    {
        $this->barcoUserService      = $user;
        $this->barcoUserGroupService = $userGroups;
        $this->utilityManager        = $utilityManager;
        $this->aipManager            = $AIPService;
        $this->userProfileManager    = $userProfileManager;
        $this->userCheckerManager    = $userCheckerManager;

        $this->barcoUserService->setLogUuid($this->logUuid);
        $this->barcoUserGroupService->setLogUuid($this->logUuid);
    }

    /**
     * Function to get Barco user details
     *
     * @param string $userId
     * @return array
     *
     * @throws InvalidResourceException
     */
    public function getUser($userId)
    {
        return ["barcoUser" => $this->barcoUserService->getUserById($userId)];
    }

    /**
     * Handler function to set peoplesoft_id of the barco user which do not have a peoplesoft_id set on their details
     * User with no peoplesoft_if will occur when user is created manually from Barco
     *
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function cleanBarcoPeoplesoftID(){
        /** @var array $barcoUsers */
        $barcoUsers = $this->entityManager
            ->getRepository(BarcoUser::class)
            ->findBy(['peoplesoft_id' => "-"], ["id" => "ASC"], 20);

        if (count($barcoUsers) > 0) {
            $emails = [];
            /** @var BarcoUser $barcoUser */
            foreach ($barcoUsers as $barcoUser) {
                $INSEADEmail = strtolower($barcoUser->getINSEADLogin());
                if(str_contains($INSEADEmail, "@insead.edu")){
                    $this->log("Checking Email: $INSEADEmail to AIP Service");
                    try {
                        $userProfiles = $this->aipManager->getUserByEMailApi($INSEADEmail);
                        if (key_exists("people", $userProfiles)){
                            if (count($userProfiles["people"]) > 0){
                                $userProfile = $this->filterAIPEmailSearch($userProfiles, $INSEADEmail);
                                if (count($userProfile) > 0) {
                                    $barcoUser->setPeopleSoftId($userProfile["emplid"]);
                                    $this->entityManager->persist($barcoUser);

                                    array_push($emails, $userProfile);
                                } else {
                                    $this->log("Unable to find Email: $INSEADEmail to AIP Service");
                                    $barcoUser->setPeopleSoftId("--");
                                    $this->entityManager->persist($barcoUser);
                                }
                            } else {
                                $this->log("Unable to find Email: $INSEADEmail to AIP Service");
                                $barcoUser->setPeopleSoftId("--");
                                $this->entityManager->persist($barcoUser);
                            }
                        } else {
                            $this->log("Unable to find Email: $INSEADEmail to AIP Service");
                            $barcoUser->setPeopleSoftId("--");
                            $this->entityManager->persist($barcoUser);
                        }
                    } catch (RequestException $e){
                        $this->log($e->getMessage());
                    }
                } else{
                    $barcoUser->setPeopleSoftId("--");
                    $this->entityManager->persist($barcoUser);
                }
            }
            $this->entityManager->flush();

            return $emails;
        } else {
            $this->log("Barco user peoplesoft cleaner. Nothing to update");
            return "Nothing to update";
        }
    }

    /**
     * Function to get Barco user group list
     *
     * @return array
     */
    public function getUserGroupList(Request $request)
    {
        $filterYear = $request->get('group_date_time');
        $filterCampus = $request->get('group_campus');

        $now = new \DateTime();
        $year = $now->format('Y');

        if ($filterYear){
            if (is_numeric($filterYear)) {
                $filterYear = trim($filterYear);
            } else {
                $filterYear = $year;
            }
        } else {
            $filterYear = $year;
        }

        if ($filterCampus){
            $filterCampus = trim((string) $filterCampus);
            if (strlen($filterCampus) === 0){
                $filterCampus = '';
            }
        } else {
            $filterCampus = '';
        }

        $queryBuilder = $this->entityManager
            ->getRepository(BarcoUserGroup::class)
            ->createQueryBuilder('bug')
            ->andWhere("year(bug.groupDateTime) = :currentYear")
            ->setParameter('currentYear', $filterYear);

        if (strlen($filterCampus) > 0){
            $queryBuilder->andWhere("bug.groupCampus = :currentCampus")
                ->setParameter('currentCampus', $filterCampus);
        }

        $queryBuilder->orderBy('bug.created', 'DESC');

        return ["barcoUserGroup" => $queryBuilder->getQuery()->getResult()];
    }

    /**
     * Function to get Barco users in a group
     *
     * @param Request $request
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getUserFromGroup($request)
    {
        $groupId = $request->query->get("barcoUserGroupId");
        if ($groupId) {
            $this->log("Getting user ids for barco group id: ".$groupId);
            $usersFromGroup = $this->barcoUserGroupService->getUsersFromGroupById($groupId);

            if (!$usersFromGroup) {
                $usersFromGroup = [];
            } else {
                $allFetchBarcoUserIds = array_column($usersFromGroup, '_id');

                foreach ($allFetchBarcoUserIds as $barcoUserId) {
                    $this->log("Checking barco user id: " . $barcoUserId);
                    /** @var BarcoUser $barcoUser */
                    $barcoUser = $this->entityManager
                        ->getRepository(BarcoUser::class)
                        ->findOneBy(['barcoUserId' => $barcoUserId]);

                    if (!$barcoUser) {
                        $barcoUser = new BarcoUser();
                        $barcoUser->setPeopleSoftId("-");
                        $barcoUser->setBarcoUserId($barcoUserId);
                        $barcoUser->setFirstName("-");
                        $barcoUser->setLastName("-");
                        $barcoUser->setDisplayName("-");
                        $barcoUser->setINSEADLogin("-");
                        $this->entityManager->persist($barcoUser);
                        $this->entityManager->flush($barcoUser);
                    }

                    try {
                        $this->getBarcoUserByPSoftOrUPNorBarcoUserID($barcoUserId);
                    } catch (OptimisticLockException $e) {
                        $this->log("OptimisticLockException: " . $e->getMessage());
                    } catch (ORMException $e) {
                        $this->log("ORMException: " . $e->getMessage());
                    }

                }

                $studyBarcoGroupUserListQuery = $this->entityManager
                    ->getRepository(BarcoUser::class)
                    ->createQueryBuilder('bug')
                    ->where('bug.barcoUserId IN (:barcoUserIds)')
                    ->setParameter('barcoUserIds', $allFetchBarcoUserIds)
                    ->getQuery();
                $studyBarcoGroupUserList = $studyBarcoGroupUserListQuery->getResult();

                $usersFromGroup = $studyBarcoGroupUserList;
            }
        } else {
            $usersFromGroup = [];
        }

        return ["barcoUser" => $usersFromGroup];
    }

    /**
     * Handler function to remove user from group
     *
     * @param $barcogroupId
     * @param $barcoUserId
     *
     * @return mixed
     */
    public function removeUserFromList($barcogroupId, $barcoUserId){
        $this->log("Request removal of Barco UserId: $barcoUserId to GroupId: $barcogroupId" );
        try {
            $this->barcoUserGroupService->removeUserFromGroupById($barcogroupId, $barcoUserId);
            $this->log("Barco UserId: $barcoUserId has been removed to Barco GroupId: $barcogroupId");
            return "1";
        } catch (InvalidResourceException $e){
            $this->log("Unable to remove Barco UserId: $barcoUserId to Barco GroupId: $barcogroupId");
            $this->log("Barco Error: ".$e->getMessage());
            return "0";
        }
    }

    /**
     * Handler function to remove user from group
     *
     * @param string $groupId
     * @return mixed
     * @throws InvalidResourceException
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function addUserToGroup(Request $request, $groupId){
        $uploadedFile = $request->files->get('file');
        $filename = $uploadedFile->getClientOriginalName();
        $fileType = $uploadedFile->getClientMimeType();
        $allowedTypes = ["application/octet-stream", "application/vnd.ms-excel", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"];

        if (!in_array($fileType, $allowedTypes)) {
            $this->log("Invalid file extension. Only allowed file is XLS and XLSX");

            throw new InvalidResourceException("Invalid file extension. Only allowed file is XLS and XLSX");
        } else {

            $uploadedFile->move($this->utilityManager->getDocumentUploadDir(), $filename);
            $fullFilePath = $this->utilityManager->getDocumentUploadDir() . "/" . $filename;

            /**  Identify the type of $inputFileName  **/
            $inputFileType = IOFactory::identify($fullFilePath);

            /**  Create a new Reader of the type that has been identified  **/
            $reader = IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);

            /**  Load $inputFileName to a Spreadsheet Object  **/
            $spreadsheet = $reader->load($fullFilePath);
            $spreadsheet->setActiveSheetIndex(0);

            /**
            * A = 0 Empl ID
            * B = 1 Last Name
            * C = 2 First Name
            * E = 4 INSEAD Login
            */
            $excelData = $spreadsheet->getActiveSheet()->toArray();
            if (count($excelData) > 0) {
                //test if first row & column is not a number
                if (!is_numeric($excelData[0][0]))
                    array_shift($excelData); // remove header

                //test if last row & first column is not a number
                if (!is_numeric($excelData[count($excelData)][0]))
                    array_pop($excelData); // remove footer
            }

            $dataToImport = [];
            foreach ($excelData as $rowData) {
                $peopleSoft_id = trim(str_replace("'", "", $rowData[0]));
                $lastName = trim((string) $rowData[1]);
                $firstName = trim((string) $rowData[2]);
                $UPN = strtolower(trim((string) $rowData[4]));

                $isError = false;
                $missingFieldsArray = [];
                if (strlen($peopleSoft_id) === 0) {
                    array_push($missingFieldsArray,"PeopleSoft ID");
                    $isError = true;
                }

                if (strlen($firstName) === 0) {
                    array_push($missingFieldsArray,"First Name");
                    $isError = true;
                }

                if (strlen($lastName) === 0) {
                    array_push($missingFieldsArray,"Last Name");
                    $isError = true;
                }

                if (strlen($UPN) === 0) {
                    array_push($missingFieldsArray,"INSEAD Email");
                    $isError = true;
                }

                if (!$isError) {
                    if (str_contains(strtolower($UPN), "@insead.edu")) {
                        /** @var BarcoUser $barcoUser */
                        $barcoUser = $this->entityManager
                            ->getRepository(BarcoUser::class)
                            ->findOneBy(['peoplesoft_id' => $peopleSoft_id]);

                        if ($barcoUser) {
                            $tmpUserArray = [
                                "remarks" => "Existing user, will be added to the group",
                                "peoplesoft_id" => $peopleSoft_id,
                                "barco_user_id" => $barcoUser->getBarcoUserId(),
                                "last_name" => $barcoUser->getLastName(),
                                "first_name" => $barcoUser->getFirstName(),
                                "upn" => $barcoUser->getINSEADLogin(),
                                "isError" => false
                            ];
                        } else {
                            $tmpUserArray = [
                                "remarks" => "Create new user and add to group",
                                "peoplesoft_id" => $peopleSoft_id,
                                "barco_user_id" => "",
                                "last_name" => $lastName,
                                "first_name" => $firstName,
                                "upn" => $UPN,
                                "isError" => false
                            ];
                        }
                    } else {
                        $tmpUserArray = [
                            "remarks" => "Not a valid INSEAD EMail",
                            "peoplesoft_id" => $peopleSoft_id,
                            "barco_user_id" => "",
                            "last_name" => $lastName,
                            "first_name" => $firstName,
                            "upn" => $UPN,
                            "isError" => true
                        ];
                    }
                } else {
                    $tmpUserArray = [
                        "remarks" => "Missing fields: ".implode(", ",$missingFieldsArray),
                        "peoplesoft_id" => $peopleSoft_id,
                        "barco_user_id" => "",
                        "last_name" => $lastName,
                        "first_name" => $firstName,
                        "upn" => $UPN,
                        "isError" => true
                    ];
                }

                array_push($dataToImport, $tmpUserArray);
            }

            return $dataToImport;
        }
    }

    /**
     * Batch enroll users
     *
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function barcoBatchEnroll(Request $request)
    {
        $users   = $request->get('users');
        $groupId = $request->get('groupId');
        if ($users && $groupId){
            if (count($users)){
                /** @var array $userToImport */
                foreach($users as &$userToImport){
                    $peoplesoft_id = trim((string) $userToImport['peoplesoft_id']);
                    $barco_user_id = trim((string) $userToImport['barco_user_id']);
                    $last_name     = trim((string) $userToImport['last_name']);
                    $first_name    = trim((string) $userToImport['first_name']);
                    $upn           = strtolower(trim((string) $userToImport['upn']));
                    $isError       = $userToImport['isError'];

                    if (!$isError) {
                        $this->log("Creating new User: ".print_r($userToImport, true));
                        try {
                            if (strlen($barco_user_id) < 1) {
                                $barco_user_id = $this->barcoUserService->createNewUser($upn, $first_name, $last_name);
                                $userToImport['barco_user_id'] = $barco_user_id;
                                $this->log("New user created $first_name $last_name id: $barco_user_id");

                                $barcoUser = $this->createBarcoUserEntity($barco_user_id, $peoplesoft_id, $first_name, $last_name, $upn);
                                $this->entityManager->persist($barcoUser);
                            }

                            if (strlen($barco_user_id) > 0){
                                $this->barcoUserGroupService->addUserToGroup($groupId, $barco_user_id);
                            }

                            $userToImport['remarks'] = "User has been added into the group";
                        } catch (InvalidResourceException $exception){
                            $userToImport['remarks'] = "Unable to create/add user in barco";
                            $this->log($exception->getMessage()." UPN: $upn");
                        }
                    } else {
                        $userToImport['remarks'] = "n/a";
                    }
                }

                $this->entityManager->flush();
            }
        }

        return $users;
    }


    /**
     * Manual enroll a user
     *
     * @param $groupId
     * @param $peopleSoftIDorINSEADEMail
     *
     * @return mixed
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function barcoManualEnroll($groupId, $peopleSoftIDorINSEADEMail){
        $this->log("Adding user with criteria ($peopleSoftIDorINSEADEMail) manually to Barco group");
        if (is_numeric($peopleSoftIDorINSEADEMail)){
            $peopleSoftID = $peopleSoftIDorINSEADEMail;
            $this->log("Adding peoplesoft id: $peopleSoftID");
            $checkBarcoUser = false;
        } else {
            $this->log("Criteria is an INSEAD EMAIL ($peopleSoftIDorINSEADEMail)");
            /** @var BarcoUser $checkBarcoUser */
            $checkBarcoUser = $this->entityManager
                ->getRepository(BarcoUser::class)
                ->findOneBy(['upn' => $peopleSoftIDorINSEADEMail]);

            if ($checkBarcoUser){
                $this->log("Criteria INSEAD EMAIL ($peopleSoftIDorINSEADEMail) found in Barco User table");
                $peopleSoftID = $checkBarcoUser->getPeopleSoftId();
            } else {
                $this->log("Criteria INSEAD EMAIL ($peopleSoftIDorINSEADEMail) not found in Barco User table");

                /** @var UserProfileCache $profileCache */
                $profileCache = $this->entityManager
                    ->getRepository(UserProfileCache::class)
                    ->findOneBy(['upn_email' => $peopleSoftIDorINSEADEMail]);

                $peopleSoftID = false;
                if ($profileCache) {
                    $this->log("Criteria INSEAD EMAIL ($peopleSoftIDorINSEADEMail) found in User Cache table");
                    $peopleSoftID = $profileCache->getUser()->getPeoplesoftId();
                }
            }
        }

        if ($peopleSoftID && !$checkBarcoUser) {
            /** @var BarcoUser $checkBarcoUser */
            $checkBarcoUser = $this->entityManager
                ->getRepository(BarcoUser::class)
                ->findOneBy(['peoplesoft_id' => $peopleSoftID]);
        } else {
            $checkBarcoUser = false;
        }

        if ($checkBarcoUser){ // check if user is in barco table
            if ($checkBarcoUser->getBarcoUserId()) {
                $this->barcoUserGroupService->addUserToGroup($groupId, $checkBarcoUser->getBarcoUserId());
            } else {
                $this->log("Unable to add user: $peopleSoftID to Barco. User is in database but no Barco User ID");
                throw new InvalidResourceException("Unable to add user.");
            }
        } else {
            if ($peopleSoftID) {
                /** @var \Insead\MIMBundle\Entity\User $user */
                $user = $this->entityManager->getRepository(\Insead\MIMBundle\Entity\User::class)
                    ->findOneBy(["peoplesoft_id" => $peopleSoftID]);
            } else {
                $user = false;
            }

            if (!$user){ // user is not on study DB
                if (!$peopleSoftID){ // the coordinator tries to add a user by INSEAD EMail
                    $peopleSoftID = false;
                    // try to search on AD first
                    $ADObject = $this->userCheckerManager->findUsersByUpn( new Request(),$peopleSoftIDorINSEADEMail );
                    if (count($ADObject) > 0){
                        if (array_key_exists('userlist',$ADObject)){
                            if (count($ADObject['userlist']) > 0) {
                                if (array_key_exists('peoplesoft_id', $ADObject['userlist'][0])) {
                                    $peopleSoftID = $ADObject['userlist'][0]['peoplesoft_id'];
                                    $this->log("PeopleSoftID found in AD: $peopleSoftID");
                                }
                            }
                        }
                    }

                    if (!$peopleSoftID){
                        $this->log("Getting Email: $peopleSoftIDorINSEADEMail to AIP Service");
                        $userProfiles = $this->aipManager->getUserByEMailApi($peopleSoftIDorINSEADEMail);
                        if (key_exists("people", $userProfiles)) {
                            if (count($userProfiles["people"]) > 0) {
                                $userProfile = $this->filterAIPEmailSearch($userProfiles, $peopleSoftIDorINSEADEMail);
                                if (count($userProfile) > 0) {
                                    $peopleSoftID = $userProfile["emplid"];
                                } else {
                                    $this->log("Unable to find Email: $peopleSoftIDorINSEADEMail to AIP Service");
                                }
                            } else {
                                $this->log("Unable to find Email: $peopleSoftIDorINSEADEMail to AIP Service");
                            }
                        } else {
                            $this->log("Unable to find Email: $peopleSoftIDorINSEADEMail to AIP Service");
                        }
                    }
                }

                if ($peopleSoftID) {
                    //fetch details to AIP Service
                    $this->userProfileManager->saveESBWithPayload($this->aipManager->getUserApi($peopleSoftID));
                    /** @var \Insead\MIMBundle\Entity\User $user */
                    $user = $this->entityManager->getRepository(\Insead\MIMBundle\Entity\User::class)
                        ->findOneBy([
                            "peoplesoft_id" => $peopleSoftID
                        ]);
                } else {
                    $user = false;
                }
            }

            if ($user) { // check if psoft is in study user table
                if ($user->getCacheProfile()){
                    /** @var UserProfileCache $cacheProfile */
                    $cacheProfile = $user->getCacheProfile();
                    $upn = strtolower($cacheProfile->getUpnEmail());
                    $this->log("User:($peopleSoftID) UPN: ($upn) found in cache profile. Creating new user to Barco.");

                    $barco_user_id = false;

                    try {

                        //check if UPN exists in Barco as SAML
                        $fetchUserDetailsFromBarco = $this->barcoUserService->findUserByAPIKey('saml', $upn);
                        if ($fetchUserDetailsFromBarco) {
                            if (array_key_exists('identifiers', $fetchUserDetailsFromBarco)) {
                                if (array_key_exists('saml', $fetchUserDetailsFromBarco['identifiers'])) {
                                    $this->log("SAML ID: $upn found in Barco");

                                    /** @var BarcoUser $checkBarcoUser */
                                    $checkBarcoUser = $this->entityManager
                                        ->getRepository(BarcoUser::class)
                                        ->findOneBy(['barcoUserId' => $fetchUserDetailsFromBarco['_id']]);

                                    if ($checkBarcoUser){
                                        $barco_user_id = $checkBarcoUser->getBarcoUserId();
                                    } else {
                                        $newBarcoUser = new BarcoUser();
                                        $this->setBarcoUser($newBarcoUser, $fetchUserDetailsFromBarco);
                                        $newBarcoUser->setPeopleSoftId($peopleSoftID);
                                        $this->entityManager->persist($newBarcoUser);
                                        $this->entityManager->flush($newBarcoUser);
                                        $barco_user_id = $newBarcoUser->getBarcoUserId();
                                    }
                                }
                            }
                        }

                    } catch (\Exception) {
                        $this->log("SAML ID: $upn do not exist in Barco");
                    }

                    try {

                        if (!$barco_user_id) {
                            $barco_user_id = $this->barcoUserService->createNewUser($upn, $cacheProfile->getFirstname(), $cacheProfile->getLastname());
                            $barcoUser = $this->createBarcoUserEntity($barco_user_id, $peopleSoftID, $cacheProfile->getFirstname(), $cacheProfile->getLastname(), $upn);
                            $this->entityManager->persist($barcoUser);
                            $this->entityManager->flush();
                        }

                    } catch (InvalidResourceException){
                        throw new InvalidResourceException("Unable to add user ($upn)....");
                    }
                    $this->log("Adding upn: $upn with barco id: $barco_user_id to group: $groupId");
                    $this->barcoUserGroupService->addUserToGroup($groupId, $barco_user_id);
                } else {
                    throw new InvalidResourceException("Unable to add user ($peopleSoftIDorINSEADEMail)...");
                }
            } else {
                throw new InvalidResourceException("Unable to add user ($peopleSoftIDorINSEADEMail)..");
            }
        }
    }

    /**
     * Handler function to add new userGroup
     *
     * @return BarcoUserGroup
     * @throws InvalidResourceException
     * @throws \Exception
     */
    public function addNewUserGroup(Request $request){
        $payload = json_decode($request->getContent(), true);

        $this->log("Starting barco new usergroup creation");
        $groupCampus = $payload['groupCampus'];
        $groupName   = $payload['groupName'];
        $groupDate   = $payload['groupDate'];
        $groupTerm   = $payload['groupTerm'];
        $groupNbr    = $payload['groupNbr'];

        if (!$groupCampus){
            $this->log("Unable to add new userGroup. Missing Group Campus");
            throw new InvalidResourceException("Unable to process request missing Group Campus");
        }

        $groupCampus = trim((string) $groupCampus);
        if ($groupCampus === ''){
            $this->log("Unable to add new userGroup. Missing Group Campus");
            throw new InvalidResourceException("Unable to process request missing Group Campus");
        }

        if (!$groupName){
            $this->log("Unable to add new userGroup. Missing Group Name.");
            throw new InvalidResourceException("Unable to process request missing Group Name");
        }

        $groupName = trim((string) $groupName);
        if ($groupName === ''){
            $this->log("Unable to add new userGroup. Missing Group Name");
            throw new InvalidResourceException("Unable to process request missing Group Name");
        }

        if (!$groupDate){
            $this->log("Unable to add new userGroup. Missing Group Date.");
            throw new InvalidResourceException("Unable to process request missing Date");
        }

        $groupDate = trim((string) $groupDate);
        if ($groupDate === ''){
            $this->log("Unable to add new userGroup. Missing Group Date");
            throw new InvalidResourceException("Unable to process request missing Date");
        }
        $groupDate = new \DateTime($groupDate);

        if (!$groupTerm){
            $groupTerm = '';
        }

        if (!$groupNbr){
            $groupNbr = '';
        }

        $groupTerm = trim((string) $groupTerm);
        $groupNbr = trim((string) $groupNbr);

        try {
            $barcoUsergroupID = $this->barcoUserGroupService->addNewUserGroup($groupName);
        } catch (InvalidResourceException){
            $this->log("Unable to create new barco group: $groupName");
            throw new InvalidResourceException("Unable to create new barco group");
        }

        $barcoUserGroup = $this->createNewBarcoUserGroup($barcoUsergroupID, $groupCampus, $groupName, $groupDate, $groupTerm, $groupNbr);
        $this->entityManager->persist($barcoUserGroup);
        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException) {
            $this->log("(OptimisticLockException)Unable to flush database after creating new UserGroup");
        } catch (ORMException) {
            $this->log("(ORMException)Unable to flush database after creating new UserGroup");
        }

        return $barcoUserGroup;
    }

    /**
     * Handler function search a user
     *
     * @return mixed
     * @throws ORMException
     */
    public function getBarcoUser(Request $request){
        $payload = json_decode($request->getContent(), true);
        $criteria = $payload['criteria'];

        $this->log("Searching barco user: $criteria");
        $barcoUsers = $this->getBarcoUserByPSoftOrUPNorBarcoUserID($criteria);

        return [$barcoUsers];
    }

    /**
     * Handler function to get all non INSEAD email account
     *
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getAllNonINSEAD()
    {
        /** @var array $barcoUsers */
        $barcoUsers = $this->entityManager
            ->getRepository(BarcoUser::class)
            ->createQueryBuilder('bug')
            ->andWhere("bug.upn not like '%@insead.edu%'")
            ->andWhere("bug.upn not like '%@insead.org%'")
            ->andWhere("bug.upn not like '%@barco.com%'")
            ->getQuery()
            ->getResult();

        $barcoUsersList = [];
        if ($barcoUsers){
            /** @var BarcoUser $barcoUser */
            foreach ($barcoUsers as $barcoUser) {
                if ($barcoUser->getBarcoUserId()){
                    $barcoUserUpdated = $this->getBarcoUserByPSoftOrUPNorBarcoUserID($barcoUser->getBarcoUserId());
                    if ($barcoUserUpdated){
                        array_push($barcoUsersList,$barcoUserUpdated);
                    }
                }
            }
        }

        return $barcoUsersList;
    }

    /**
     * Function to search and update study database the barco user
     *
     * @param $criteria
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function getBarcoUserByPSoftOrUPNorBarcoUserID($criteria){
        /** @var BarcoUser $barcoUsers */
        $barcoUsers = $this->entityManager
            ->getRepository(BarcoUser::class)
            ->createQueryBuilder('bu')
            ->orWhere("bu.peoplesoft_id = :criteria")
            ->orWhere("bu.upn = :criteria")
            ->orWhere("bu.barcoUserId = :criteria")
            ->setParameter('criteria', $criteria)
            ->getQuery()
            ->getResult();

        $barcoUser = false;
        if ($barcoUsers && count($barcoUsers) > 0){
            $barcoUser = $barcoUsers[0];
            if ($barcoUser->getBarcoUserId()){
                try {
                    $fetchUserDetailsFromBarco = $this->barcoUserService->getUserById($barcoUser->getBarcoUserId());
                    if ($fetchUserDetailsFromBarco) {
                        $this->setBarcoUser($barcoUser,$fetchUserDetailsFromBarco);
                        $this->entityManager->persist($barcoUser);
                        $this->entityManager->flush($barcoUser);
                    }
                } catch (InvalidResourceException){
                    $this->log("Unable to find from barcoid: ".$barcoUser->getBarcoUserId());
                }
            }
        }

        return $barcoUser;
    }

    private function setBarcoUser(BarcoUser &$barcoUser, $barcoObject){
        $barcoUser->setBarcoUserId($barcoObject['_id']);
        $barcoUser->setFirstName($barcoObject['name']['first']);
        $barcoUser->setlastName($barcoObject['name']['last']);
        $barcoUser->setDisplayName($barcoObject['name']['displayName']);

        if (array_key_exists('identifiers',$barcoObject)) {
            if (array_key_exists('saml', $barcoObject['identifiers'])) {
                $barcoUser->setINSEADLogin($barcoObject['identifiers']['saml']);
            }
        } else {
            $barcoUser->setINSEADLogin($barcoObject['email']);
        }
    }

    /**
     * Handler function delete a user in Barco
     *
     * @param $id
     * @return mixed
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteUser(Request $request, $id){
        /** @var BarcoUser $barcoUsers */
        $barcoUsers = $this->entityManager
            ->getRepository(BarcoUser::class)
            ->findOneBy(['id' => $id]);

        if ($barcoUsers){
            $this->barcoUserService->deleteUserById($barcoUsers->getBarcoUserId());
            $this->entityManager->remove($barcoUsers);
            $this->entityManager->flush();
            return true;
        } else {
            throw new InvalidResourceException("Invalid deletion request: ".$id);
        }
    }

    /**
     * Handler function update a user and update in barco
     *
     * @return mixed
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateBarcoUser(Request $request){
        $payload = json_decode($request->getContent(), true);

        $id           = $payload['id'];
        $display_name = $payload['display_name'];

        if (!$id){
            $this->log("ID is missing in the request");
            throw new InvalidResourceException("Unable to update user.");
        }

        $id = trim((string) $id);
        if (strlen($id) < 1){
            $this->log("ID is blank in the request");
            throw new InvalidResourceException("Unable to update user.");
        }

        /** @var BarcoUser $barcoUsers */
        $barcoUsers = $this->entityManager
            ->getRepository(BarcoUser::class)
            ->findOneBy(['id' => $id]);

        if ($barcoUsers){
            $parameters = [];
            if ($display_name) {
                $display_name = trim((string) $display_name);
                if (strlen($display_name) > 0) {
                    if (!$parameters['name']){
                        $parameters['name'] = [];
                    }

                    $parameters['name']['displayName'] = $display_name;
                    $barcoUsers->setDisplayName($display_name);
                }
            }

            if (count($parameters) > 0) {

                if (!$parameters['name']['first']){
                    $parameters['name']['first'] = $barcoUsers->getFirstName();
                }

                if (!$parameters['name']['last']){
                    $parameters['name']['last'] = $barcoUsers->getLastName();
                }
                
                $this->log("Updating barco user id: " . $barcoUsers->getBarcoUserId()." Params: ".print_r($parameters,true));
                try {
                    $this->barcoUserService->updateUserById($barcoUsers->getBarcoUserId(), $parameters);
                    $this->entityManager->persist($barcoUsers);
                    $this->entityManager->flush();
                } catch (InvalidResourceException){
                    $this->log("Unable to update barco user id (".$barcoUsers->getBarcoUserId().")");
                    throw new InvalidResourceException("Unable to update user.");
                }
            } else {
                $this->log("Nothing to update");
            }
        } else {
            $this->log("Unable to find user in barco user table with ID: $id");
            throw new InvalidResourceException("Unable to update user.");
        }

        return $barcoUsers;
    }

    /**
     * Handler function when ESB send a profile update it will update Barco
     *
     * @param $peopleSoftID
     * @return void
     */
    public function updateBarcoUserFromAIP($peopleSoftID){
        /** @var \Insead\MIMBundle\Entity\User $user */
        $user = $this->entityManager->getRepository(\Insead\MIMBundle\Entity\User::class)
            ->findOneBy(["peoplesoft_id" => $peopleSoftID]);

        if ($user) {
            if ($user->getCacheProfile()) {
                /** @var UserProfileCache $userProfileCache */
                $userProfileCache = $user->getCacheProfile();

                $this->log("updateBarcoUserFromAIP: $peopleSoftID");
                /** @var BarcoUser $barcoUser */
                $barcoUser = $this->entityManager
                    ->getRepository(BarcoUser::class)
                    ->findOneBy(['peoplesoft_id' => $peopleSoftID]);

                if ($barcoUser){
                    $barcoUserId = $barcoUser->getBarcoUserId();

                    $barcoUser->setFirstName($userProfileCache->getFirstname());
                    $barcoUser->setLastName($userProfileCache->getLastname());
                    $barcoUser->setINSEADLogin($userProfileCache->getUpnEmail());
                    $display_name = $userProfileCache->getFirstname()." ".$userProfileCache->getLastname();
                    $barcoUser->setDisplayName($display_name);

                    try {
                        $this->entityManager->persist($barcoUser);
                        $this->entityManager->flush();

                        $parameters['name']['first'] = $barcoUser->getFirstName();
                        $parameters['name']['last'] = $barcoUser->getLastName();
                        $parameters['name']['displayName'] = $barcoUser->getDisplayName();
                        $parameters['email'] = $barcoUser->getINSEADLogin();
                        $parameters['identifiers']['saml'] = $barcoUser->getINSEADLogin();

                        $this->barcoUserService->updateUserById($barcoUserId, $parameters);
                        $this->log("User: $peopleSoftID has been update in Barco from AIP request");
                    } catch (\Exception){
                        $this->log('Unable to update barco from updateBarcoUserFromAIP');
                    }

                } else {
                    $this->log("User not in BarcoUser table: $peopleSoftID");
                }
            } else {
                $this->log("User not in Cache: $peopleSoftID");
            }
        } else {
            $this->log("Unable to find in User table user: $peopleSoftID");
        }
    }

    /**
     * Handler function to add/update user group details to DB.
     * This could happen if new group is created manually or the group is in different ENV in Study
     *
     * @return array|BarcoUserGroup
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws \Exception
     */
    public function addUserGroupToDB(Request $request){
        $groupID     = $request->get('groupId');
        $groupCampus = $request->get('groupCampus');
        $groupName   = $request->get('groupName');
        $groupDate   = $request->get('groupDate');
        $groupTerm   = $request->get('groupTerm');
        $groupNbr    = $request->get('groupNbr');

        if (!$groupID || !$groupCampus || !$groupName || !$groupDate){
            throw new InvalidResourceException("Missing Barco  UserGroup Fields");
        }

        $groupID     = trim((string) $groupID);
        $groupCampus = trim((string) $groupCampus);
        $groupName   = trim((string) $groupName);
        $groupDate   = trim((string) $groupDate);
        $groupTerm   = trim((string) $groupTerm);
        $groupNbr    = trim((string) $groupNbr);

        if (strlen($groupID) < 1 || strlen($groupCampus) < 1 || strlen($groupName) < 1 || strlen($groupDate) < 1){
            throw new InvalidResourceException("Missing Barco  UserGroup Fields");
        }

        $groupDate = new \DateTime($groupDate);

        try {
            $this->barcoUserGroupService->getUserGroupById($groupID);
        } catch (InvalidResourceException){
            throw new InvalidResourceException("Group ID not existing in Barco");
        }

        /** @var BarcoUserGroup $barcoUserGroup */
        $barcoUserGroup = $this->entityManager
            ->getRepository(BarcoUserGroup::class)
            ->findOneBy(['groupId' => $groupID]);

        if ($barcoUserGroup){
            $barcoUserGroup->setGroupCampus($groupCampus);
            $barcoUserGroup->setGroupName($groupName);
            $barcoUserGroup->setGroupDate($groupDate);
            $barcoUserGroup->setGroupTerm($groupTerm);
            $barcoUserGroup->setGroupClassNbr($groupNbr);
        } else {
            $barcoUserGroup = $this->createNewBarcoUserGroup($groupID, $groupCampus, $groupName, $groupDate, $groupTerm, $groupNbr);
        }

        $this->entityManager->persist($barcoUserGroup);
        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException) {
            $this->log("(OptimisticLockException)Unable to flush database after creating new UserGroup");
        } catch (ORMException) {
            $this->log("(ORMException)Unable to flush database after creating new UserGroup");
        }

        return $barcoUserGroup;
    }

    /**
     * Handler to delete Barco User group from DB and Barco API
     * @param $barcoGroupId
     * @return BarcoUserGroup
     *
     * @throws InvalidResourceException
     */
    public function deleteBarcoUserGroup($barcoGroupId)
    {
        /** @var BarcoUserGroup $barcoGroup */
        $barcoGroup = $this->entityManager
            ->getRepository(BarcoUserGroup::class)
            ->findOneBy(['groupId' => $barcoGroupId]);

        if (!$barcoGroup){
            $this->log("Barco user group: $barcoGroupId is not on database. Not proceeding to delete the Group");
            throw new InvalidResourceException("Invalid user group ID: $barcoGroupId");
        } else {
            try {
                $this->barcoUserGroupService->deleteUserGroupById($barcoGroupId);
                $this->entityManager->remove($barcoGroup);
                $this->entityManager->flush();
            } catch (\Exception $e){
                $this->log("Unable to delete User Group: ".$e->getMessage());
                throw new InvalidResourceException("Unable to delete User Group");
            }
        }

        return $barcoGroup;
    }

    /**
     * Filter the AIP Search result by Email
     *
     * @param $AIPSearchResult
     * @param $emailToFind
     * @return array
     */
    public function filterAIPEmailSearch($AIPSearchResult, $emailToFind){
        if (count($AIPSearchResult["people"]) > 0) {
            foreach ($AIPSearchResult["people"] as $user) {
                if (strtolower((string) $user["email_address"]) === strtolower((string) $emailToFind)){
                    return $user;
                }
            }
        }

        return [];
    }

    /**
     * Create new Barco User Entity
     * @param $barco_user_id
     * @param $peoplesoft_id
     * @param $first_name
     * @param $last_name
     * @param $upn
     * @return BarcoUser
     */
    public function createBarcoUserEntity($barco_user_id, $peoplesoft_id, $first_name, $last_name, $upn){
        $barcoUser = new BarcoUser();
        $barcoUser->setBarcoUserId($barco_user_id);
        $barcoUser->setPeopleSoftId($peoplesoft_id);
        $barcoUser->setFirstName($first_name);
        $barcoUser->setlastName($last_name);
        $barcoUser->setDisplayName($first_name." ".$last_name);
        $barcoUser->setINSEADLogin($upn);

        return $barcoUser;
    }

    /**
     * Create new Barco Usergroup Entity
     *
     * @param $groupID
     * @param $groupCampus
     * @param $groupName
     * @param $groupDate
     * @param $groupTerm
     * @param $groupNbr
     * @return BarcoUserGroup
     */
    public function createNewBarcoUserGroup($groupID, $groupCampus, $groupName, $groupDate, $groupTerm, $groupNbr){
        $barcoUsergroup = new BarcoUserGroup();
        $barcoUsergroup->setGroupID($groupID);
        $barcoUsergroup->setGroupCampus($groupCampus);
        $barcoUsergroup->setGroupName($groupName);
        $barcoUsergroup->setGroupDate($groupDate);
        $barcoUsergroup->setGroupTerm($groupTerm);
        $barcoUsergroup->setGroupClassNbr($groupNbr);

        return $barcoUsergroup;
    }
}
