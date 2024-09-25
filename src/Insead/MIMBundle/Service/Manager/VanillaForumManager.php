<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Insead\MIMBundle\Entity\CourseSubscription;
use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\User;

use Insead\MIMBundle\Entity\VanillaConversation;
use Insead\MIMBundle\Entity\VanillaProgrammeDiscussion;
use Insead\MIMBundle\Entity\VanillaProgrammeGroup;
use Insead\MIMBundle\Entity\VanillaUserGroup;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use mysql_xdevapi\Exception;
use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\StudyNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use Insead\MIMBundle\Service\BoxManager\BoxManager;
use Insead\MIMBundle\Service\StudyCourseBackup as Backup;
use Insead\MIMBundle\Service\S3ObjectManager;

use Insead\MIMBundle\Service\Vanilla\Group as VanillaGroup;
use Insead\MIMBundle\Service\Vanilla\Category as VanillaCategory;
use Insead\MIMBundle\Service\Vanilla\Discussion as VanillaDiscussion;
use Insead\MIMBundle\Service\Vanilla\Conversation as VanillaConversationAPI;

use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\VanillaGenericException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class VanillaForumManager extends Base
{
    protected $s3;
    protected $env;
    protected $default_category;
    protected $vanillaGroup;
    protected $vanillaCategory;
    protected $vanillaDiscussion;
    protected $vanillaConversationAPI;

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_VPG = "VanillaProgrammeGroup";
    public static $ENTITY_VPD = "VanillaProgrammeDiscussion";
    public static $ENTITY_VUG = "VanillaUserGroup";
    public static $ENTITY_VC  = "VanillaConversation";

    private $vanilla_base_url;

    private $vanillaAPILimitRequest   = 5;
    private $conversationLimitRequest = 10;

    private $peopleType = ['getCoordination', 'getStudents', 'getFaculty', 'getContacts', 'getDirectors', 'getInseadTeam'];

    public function loadServiceManager(S3ObjectManager $s3, $config, VanillaGroup $vanillaGroup, VanillaCategory $vanillaCategory, VanillaDiscussion $vanillaDiscussion, VanillaConversationAPI $vanillaConversationAPI, array $vanillaConfig)
    {
        $this->s3                       = $s3;
        $this->vanillaGroup             = $vanillaGroup;
        $this->vanillaCategory          = $vanillaCategory;
        $this->vanillaDiscussion        = $vanillaDiscussion;
        $this->vanillaConversationAPI   = $vanillaConversationAPI;
        $this->env                      = $config["symfony_environment"];
        $this->default_category         = $vanillaConfig["vanilla_category"];
        $this->vanilla_base_url         = $vanillaConfig['vanilla_base_url'];
        $this->vanillaAPILimitRequest   = intval($vanillaConfig['vanilla_conversation_limit']);


    }

    /**
     * Check if there is a user included in the programme which do have a vanilla user id
     * @param Request  $request       Request Object
     * @param integer  $programmeId   id of the programme
     *
     * @throws
     *
     * @return array
     */
    public function getForumStatus(Request $request, $programmeId)
    {
        $em = $this->entityManager;
        $checklist = [];

        /** @var array $checklistDescriptions */
        $checklistDescriptions = ["People with forum ID", "Initial forum group"];

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);


        //If there is an existing non null group id then let it through
        $query  = $em->createQuery(
            'SELECT vpg FROM Insead\MIMBundle\Entity\VanillaProgrammeGroup vpg
            WHERE vpg.programme = :programme_id and vpg.vanillaGroupId IS NOT NULL'
        )
        ->setParameter('programme_id', $programmeId);

        if (count($query->getResult()) > 0){
            $status = true;
            $message = "Programme is now ready for Forum integration";
            $checklist = [];
        } else {

            if ($programme) {
                if ($programme->getWithDiscussions()) {
                    $status = true;
                    $message = "Programme is now ready for Forum integration";

                    $peoples = $this->getPeopleList($programme);
                    $countOfPeopleWithNoVanillaId = 0;

                    // Block if user does not have vanilla forums yet
                    /** @var User $student */
                    foreach ($peoples as $user) {
                        if ($user->getVanillaUserId() == "") {
                            $status = false;
                        } else {
                            $countOfPeopleWithNoVanillaId++;
                        }
                    }


                    if (count($peoples) > 0) { // meaning there's people included in the programme

                        if ($status) {
                            $vanillaGroups = $em
                                ->getRepository(VanillaProgrammeGroup::class)
                                ->findBy(['programme' => $programme]);

                            if (count($vanillaGroups) < 1) {
                                $status = false;
                                $message = "Please wait while we create your initial group";
                                $checklist = ["checklist" => [["description" => $checklistDescriptions[0], "task" => true], ["description" => $checklistDescriptions[1], "task" => false]], "progressBar" => ["min" => $countOfPeopleWithNoVanillaId, "max" => count($peoples)]];
                            }
                        } else {
                            $status = false;
                            $message = "Programme is not yet ready for Forum integration";
                            $checklist = ["checklist" => [["description" => $checklistDescriptions[0], "task" => false], ["description" => $checklistDescriptions[1], "task" => false]], "progressBar" => ["min" => $countOfPeopleWithNoVanillaId, "max" => count($peoples)]];
                        }
                    } else {
                        $message = "There is no participant/coordinator that are currently added in the people page";
                        $checklist = ["checklist" => [["description" => $checklistDescriptions[0], "task" => false], ["description" => $checklistDescriptions[1], "task" => false]], "progressBar" => ["min" => 0, "max" => 100]];
                    }
                } else {
                    $status = false;
                    $message = "Forum is not enabled for programme";
                    $checklist = ["checklist" => [["description" => $checklistDescriptions[0], "task" => false], ["description" => $checklistDescriptions[1], "task" => false]], "progressBar" => ["min" => 0, "max" => 100]];
                }
            } else {
                $status = false;
                $message = "Programme not found";
                $checklist = ["checklist" => [["description" => $checklistDescriptions[0], "task" => false], ["description" => $checklistDescriptions[1], "task" => false]], "progressBar" => ["min" => 0, "max" => 100]];
            }
        }

        return ["result" => $status, "message" => $message, "remarks" => $checklist];
    }

    /**
     * Create initial group of a programme
     *
     * @throws NotSupported
     */
    public function createInitialGroup(Request $request){
        $em = $this->entityManager;

        $programmesWithDiscussions = $em
            ->getRepository(Programme::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.programmegroups', 'v')
            ->where('p.withDiscussions = 1')
            ->groupBy('p.id')
            ->having('COUNT(v.id) = 0')
            ->setMaxResults($this->vanillaAPILimitRequest)
            ->getQuery()
            ->getResult();

        $actualNumberOfRequestToVanilla = 0;
        /** @var Programme $programme */
        foreach ($programmesWithDiscussions as $programme) {
            $programme->setOverriderReadonly(true);

            /** @var array $groups */
            $groups = $this->getForumGroupList($request, $programme->getId());
            $groups = $groups['vanillaGroup'];

            if (count($groups) < 1) {
                /** @var array $statusCheck */
                $statusCheck = $this->getForumStatus($request, $programme->getId());
                if ($statusCheck['remarks']['checklist'][0]['task']) {
                    $this->log("Programme ID: " . $programme->getId() . " does not have vanilla group yet. Initializing create of group 'EVERYONE'");

                    $coordinatorsList = $this->getPeopleList($programme, ['getCoordination']);

                    if (count($coordinatorsList) < 1) {
                        $this->log("There is no 'COORDINATOR' for programme: " . $programme->getId());
                    } else {
                        try {

                            $vanillaGroupName = $this->env . '~' . $programme->getId() . '~~~Everyone';
                            $groupDescription = $programme->getCode() . '~' . $programme->getName();
                            $groupInfo = ["name" => $vanillaGroupName, "description" => $groupDescription, "format" => "text", "iconUrl" => "", "bannerUrl" => "", "privacy" => "secret"];

                            $response = json_decode((string) $this->vanillaGroup->create($groupInfo));
                            $vanillaGroupId = $response->groupID;
                            $actualNumberOfRequestToVanilla++;

                            $vanillaProgrammeGroup = new VanillaProgrammeGroup();
                            $vanillaProgrammeGroup->setProgramme($programme);
                            $vanillaProgrammeGroup->setVanillaGroupName('Everyone');
                            $vanillaProgrammeGroup->setVanillaGroupId($vanillaGroupId);
                            $vanillaProgrammeGroup->setVanillaGroupDescription($groupDescription);
                            $vanillaProgrammeGroup->setInitial(true);
                            $vanillaProgrammeGroup->setCourse(null);
                            $this->createRecord(self::$ENTITY_VPG, $vanillaProgrammeGroup);
                            $em->persist($vanillaProgrammeGroup);
                            $em->flush($vanillaProgrammeGroup);
                        } catch (\Exception) {
                            $this->log("General exception: Unable to create group for programme id: " . $programme->getId());
                        }
                    }
                }
            }
        }
    }

    /**
     * Function that will add user to a specific group in vanilla
     * @param $vanillaGroupId
     * @param $userInfo
     * @return bool
     */
    private function addUserToGroup($vanillaGroupId, $userInfo){
        try {
            $this->vanillaGroup->addMember($vanillaGroupId,$userInfo);
            $this->mapUserGroup($vanillaGroupId,$userInfo['userID'],true,false);
            return true;
        } catch (VanillaGenericException){
            $this->log("Error occurred while adding vanilla user id: ".$userInfo['userID']." to group id: ".$vanillaGroupId);
        } catch (Exception){
            $this->log("Unable to add vanilla user id: ".$userInfo['userID']." to group id: ".$vanillaGroupId);
        }

        return false;
    }

    /**
     * Handler for mapping user and vanilla group
     * @param $vanillaGroupId
     * @param $vanillaUserId
     * @param $isAdded
     * @param $isRemove
     *
     * @throws
     */
    private function mapUserGroup($vanillaGroupId,$vanillaUserId,$isAdded,$isRemove){
        $em = $this->entityManager;

        /** @var VanillaProgrammeGroup $vanillaGroup */
        $vanillaGroup = $em
            ->getRepository(VanillaProgrammeGroup::class)
            ->findOneBy(['vanillaGroupId' => $vanillaGroupId]);

        /** @var User $user */
        $user = $em->getRepository(User::class)
            ->findOneBy(['vanillaUserId' => $vanillaUserId]);

        /** @var VanillaUserGroup $user */
        $vanillaUserGroup = $em->getRepository(VanillaUserGroup::class)
            ->findOneBy(['user' => $user, 'group' => $vanillaGroup]);

        $isNewRecord = false;
        if (!$vanillaUserGroup){
            $isNewRecord = true;
            $vanillaUserGroup = new VanillaUserGroup();
        }


        $vanillaUserGroup->setGroup($vanillaGroup);
        $vanillaUserGroup->setUser($user);
        $vanillaUserGroup->setAdded($isAdded);
        $vanillaUserGroup->setRemove($isRemove);

        if ($isNewRecord){
            $this->createRecord(self::$ENTITY_VUG, $vanillaUserGroup);
        }

        $em->persist($vanillaUserGroup);
        $em->flush($vanillaUserGroup);
    }

    /**
     * Get list of groups with vanilla group id under the programme
     * @param Request   $request       Request Object
     * @param integer   $programmeId   id of the programme
     *
     * @throws
     *
     * @return array
     */
    public function getForumGroupList(Request $request, $programmeId){
        $em = $this->entityManager;
        $query  = $em->createQuery(
            'SELECT vpg FROM Insead\MIMBundle\Entity\VanillaProgrammeGroup vpg
            WHERE vpg.programme = :programme_id and vpg.vanillaGroupId IS NOT NULL'
        )
        ->setParameter('programme_id', $programmeId);

        /** @var array $grouplist */
        $grouplist = $query->getResult();

        /** @var array $vanillagrouplist */
        $vanillaGroupList = [];
        if (count($grouplist) > 0){

            $programme = $this->getProgrammeObject($programmeId);

            $peoples = $this->getPeopleList($programme);

            /** @var VanillaProgrammeGroup $group */
            foreach($grouplist  as $group){
                $groupDetails = $this->getGroupDetails($programmeId,$peoples,$group);

                foreach(['included','excluded'] as $userStatus) {
                    if (count($groupDetails['groupMembers'][$userStatus]) > 0) {
                        foreach ($groupDetails['groupMembers'][$userStatus] as &$userDetail) {
                            /** @var User $user */
                            $user = $em->getRepository(User::class)
                                ->findOneBy(['id' => $userDetail['id']]);

                            $membership = [];
                            $vanillaUserGroup = $em->getRepository(VanillaProgrammeGroup::class)
                                ->createQueryBuilder('vpg')
                                ->leftJoin('vpg.vanillaUserGroup', 'vug')
                                ->where('vpg.programme = :programmeId and vug.user = :userId and vug.added = 1')
                                ->select('vpg.name as groupName')
                                ->setParameter('programmeId', $programme->getId())
                                ->setParameter('userId',$user->getId())
                                ->getQuery()
                                ->getArrayResult();

                            foreach ($vanillaUserGroup as $myVanillaGroup)
                                array_push($membership, $myVanillaGroup['groupName']);

                            $userDetail['memberships'] = $membership;
                        }
                    }
                }

                array_push($vanillaGroupList,$groupDetails);
            }
        }

        return ['vanillaGroup' => $vanillaGroupList];
    }

    /**
     * Function that will provide the detail of the Group
     *
     * @param $programmeID
     * @param $listOfPeoples
     * @return array
     */
    private function getGroupDetails($programmeID, $listOfPeoples, VanillaProgrammeGroup $group){
        $groupName = $group->getVanillaGroupName();
        $cleanedGroupName = $group->getVanillaGroupName();

        $splitName = explode("~~~",$groupName);
        if (count($splitName) > 1)
            $cleanedGroupName = $splitName[1];


        $em = $this->entityManager;
        $vanillaUsers = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->join('u.vanillaUserGroup','vug')
            ->where('vug.group = :groupId')
            ->select('u.vanillaUserId, vug.added, vug.remove')
            ->setParameter('groupId', $group->getId())
            ->getQuery()
            ->getResult();


        $groupMembers = [];
        array_push($groupMembers, ['userID' => 'na']);
        /** @var User $vanillaUser */
        foreach ($vanillaUsers as $vanillaUser){
            array_push($groupMembers, ['userID' => $vanillaUser['vanillaUserId'], 'isAdded' => $vanillaUser['added'], 'isRemove' => $vanillaUser['remove']]);
        }

        return ['programmeId' => $programmeID, 'groupId' => $group->getVanillaGroupId(), 'name' => $cleanedGroupName, 'description' => $group->getVanillaGroupDescription(), 'isInitial' => $group->getInitial(), 'discussions' => $this->listDiscussionByGroupId($group->getVanillaGroupId()), 'groupMembers' => $this->matchUsersWithVanillaMembers($listOfPeoples,$groupMembers)];
    }

    /**
     * Create a group under a programme and send the request to Vanilla API
     * @param Request  $request       Request Object
     * @param integer  $programmeId   id of the programme
     *
     * @throws
     *
     * @return array
     */
    public function createForumGroup(Request $request, $programmeId){
        $data = $this->loadDataFromRequest( $request, 'name,description' );

        $preferredVanillaGroupName = $this->env . '~' . $programmeId . '~~~' . trim((string) $data['name']);

        $programme = $this->getProgrammeObject($programmeId);

        $groupInfo = ["name" => $preferredVanillaGroupName, "description" => $data['description'], "format" => "text", "iconUrl" => "", "bannerUrl" => "", "privacy" => "secret"];

        $response = json_decode((string) $this->vanillaGroup->create($groupInfo));
        if ($response) {
            $vanillaProgrammeGroup = new VanillaProgrammeGroup();
            $vanillaProgrammeGroup->setProgramme($programme);
            $vanillaProgrammeGroup->setVanillaGroupName(trim((string) $data['name']));
            $vanillaProgrammeGroup->setVanillaGroupId($response->groupID);
            $vanillaProgrammeGroup->setVanillaGroupDescription(trim((string) $data['description']));
            $vanillaProgrammeGroup->setInitial(false);
            $vanillaProgrammeGroup->setCourse(null);
            $responseObj = $this->createRecord(self::$ENTITY_VPG, $vanillaProgrammeGroup);

            if(!$responseObj){
                throw new InvalidResourceException('Unable to create new group');
            }
        }

        return $this->getForumGroupList($request, $programmeId);
    }

    /**
     * Delete a group under a programme and send the request to Vanilla API
     *
     * @param $programmeId
     * @param $groupId
     * @return bool
     * @throws InvalidResourceException
     */
    public function deleteForumGroup(Request $request, $programmeId, $groupId){

        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);


        /** @var VanillaProgrammeGroup $userVPG */
        $vanillaProgrammeGroup = $em->getRepository(VanillaProgrammeGroup::class)
            ->findOneBy(['vanillaGroupId' => $groupId, 'programme' => $programme]);

        if ($vanillaProgrammeGroup) {
            $vanillaProgrammeDiscussionList = $em->getRepository(VanillaProgrammeDiscussion::class)
                ->findBy(['programme' => $programme, 'groupId' => $vanillaProgrammeGroup->getVanillaGroupId()]);

            try {
                $this->log('Start deleting vanilla group: '.$groupId);
                $this->vanillaGroup->delete($groupId);
                $this->log('Group: '.$groupId.' has been removed in vanilla');

                /** @var VanillaProgrammeDiscussion $vanillaProgrammeDiscussion */
                foreach ($vanillaProgrammeDiscussionList as $vanillaProgrammeDiscussion) {
                    $em->remove($vanillaProgrammeDiscussion);
                    $em->flush($vanillaProgrammeDiscussion);
                }
                $this->log('Discussions has been removed from the database. Deletion of Group: '.$groupId);

                $em->remove($vanillaProgrammeGroup);
                $em->flush($vanillaProgrammeGroup);

                $this->log("Group: ".$groupId." has been deleted from programme: ".$programmeId);

                return true;
            } catch (\Exception $e) {
                $this->log('Error deleting group: '.$groupId.'\nError: '.$e->getMessage());
                throw new InvalidResourceException('Unable to delete group');
            }
        } else {
            throw new InvalidResourceException('Group not found');
        }
    }

    /**
     * Handles the request for adding member to a group and put it in the queue.
     *
     * @param $programmeId
     * @param $groupId
     * @param $vanillaUserId
     * @return array
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addMemberToGroupQueue(Request $request, $programmeId, $groupId, $vanillaUserId){
        $em = $this->entityManager;

        /** @var User $user */
        $user = $em->getRepository(User::class)
            ->findOneBy(['vanillaUserId' => $vanillaUserId]);

        if ($user){
            $userId = $user->getId();

            /** @var VanillaProgrammeGroup $userVPG */
            $userVPG = $em->getRepository(VanillaProgrammeGroup::class)
                ->findOneBy(['vanillaGroupId' => $groupId]);


            if (!$userVPG){
                $this->log('[Adding vanilla] Invalid vanilla group id: '.$groupId);
                throw new InvalidResourceException('Invalid vanilla group id: '.$groupId);
            }

            /** @var VanillaUserGroup $userVUG */
            $userVUG = $em->getRepository(VanillaUserGroup::class)
                ->findOneBy(['group' => $userVPG->getId(), 'user' => $userId]);

            if (!$userVUG){
                $vug = new VanillaUserGroup();
                $vug->setGroup($userVPG);
                $vug->setUser($user);
                $vug->setAdded(false);
                $vug->setRemove(false);
                $this->createRecord(self::$ENTITY_VUG, $vug);
                $em->persist($vug);
                $em->flush();
            }

            return $this->getForumGroupList($request,$programmeId);
        } else {
            $this->log('[Adding vanilla] Invalid vanilla user id: ' . $vanillaUserId);
            throw new InvalidResourceException('Invalid vanilla user id: '.$vanillaUserId);
        }
    }

    /**
     * Add member to a group and send the request to Vanilla API
     *
     * @param $programmeId
     * @param $groupId
     * @param $vanillaUserId
     * @throws InvalidResourceException
     * @return bool
     */
    public function addMemberToGroup(Request $request, $programmeId, $groupId, $vanillaUserId){

        $em = $this->entityManager;

        $query  = $em->createQuery(
            'SELECT vpg FROM Insead\MIMBundle\Entity\VanillaProgrammeGroup vpg
            WHERE vpg.programme = :programme_id and vpg.vanillaGroupId = :vanillaGroupId'
        )
            ->setParameter('programme_id', $programmeId)
            ->setParameter('vanillaGroupId', $groupId);

        $group = $query->getResult();
        if (count($group) > 0){

            /** @var User $user */
            $user = $em->getRepository(User::class)
                ->findOneBy(['vanillaUserId' => $vanillaUserId]);

            if ($user) {
                /** @var CourseSubscription $programmeUser */
                $programmeUser = $em
                    ->getRepository(CourseSubscription::class)
                    ->findOneBy(['user' => $user->getId(), 'programme' => $programmeId]);

                if ($programmeUser){
                    $role = "member";
                    if ($programmeUser->getRole()->getName() === "coordinator"){
                        $role = "leader";
                    }

                    $userInfo = ["userID" => $vanillaUserId, "role" => $role];
                    if ($this->addUserToGroup($groupId,$userInfo)){
                        return true;
                    } else {
                        throw new InvalidResourceException('User is already added in the group');
                    }
                } else {
                    throw new InvalidResourceException('User not found in programme');
                }
            } else {
                throw new InvalidResourceException('Vanilla user id not found');
            }
        } else {
            throw new InvalidResourceException('Group not found');
        }
    }

    /**
     * Handles the request for removing member to a group and put it in the queue.
     *
     * @param $programmeId
     * @param $groupId
     * @param $vanillaUserId
     * @return array
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeMemberFromGroupQueue(Request $request, $programmeId, $groupId, $vanillaUserId){
        $em = $this->entityManager;

        /** @var User $user */
        $user = $em->getRepository(User::class)
            ->findOneBy(['vanillaUserId' => $vanillaUserId]);

        if ($user){
            $userId = $user->getId();

            /** @var VanillaProgrammeGroup $userVPG */
            $userVPG = $em->getRepository(VanillaProgrammeGroup::class)
                ->findOneBy(['vanillaGroupId' => $groupId]);

            if (!$userVPG){
                $this->log('[Removing] Invalid vanilla group id: '.$groupId);
                throw new InvalidResourceException('Invalid vanilla group id: '.$groupId);
            }

            /** @var VanillaUserGroup $userVUG */
            $userVUG = $em->getRepository(VanillaUserGroup::class)
                ->findOneBy(['group' => $userVPG->getId(), 'user' => $userId]);

            if ($userVUG){
                $userVUG->setRemove(true);
                $em->persist($userVUG);
                $em->flush();
            } else {
                $this->log('[Removing] Invalid vanilla group id: '.$groupId.' for user id: '.$userId);
                throw new InvalidResourceException('Invalid vanilla group id: '.$groupId);
            }

            return $this->getForumGroupList($request,$programmeId);
        } else {
            $this->log('[Removing] Invalid vanilla user id: ' . $vanillaUserId);
            throw new InvalidResourceException('Invalid vanilla user id: '.$vanillaUserId);
        }
    }

    /**
     * Remove member to a group and send the request to Vanilla API
     * @param Request  $request        Request Object
     * @param integer  $programmeId    id of the programme
     * @param integer  $groupId        id of the group
     * @param integer  $vanillaUserId  vanilla user id of the Study User
     *
     * @throws
     *
     * @return boolean
     */
    public function removeMemberFromGroup(Request $request, $programmeId, $groupId, $vanillaUserId){
        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);

        /** @var VanillaProgrammeGroup $userVPG */
        $userVPG = $em->getRepository(VanillaProgrammeGroup::class)
            ->findOneBy(['vanillaGroupId' => $groupId, 'programme' => $programme]);
        if ($userVPG){

            /** @var User $user */
            $user = $em->getRepository(User::class)
                ->findOneBy(['vanillaUserId' => $vanillaUserId]);

            if ($user) {
                try{
                    $this->vanillaGroup->removeMember($groupId,$vanillaUserId);
                    $this->log("User (".$user->getPeoplesoftId().") successfully removed from group: ".$userVPG->getVanillaGroupName());

                    /** @var VanillaUserGroup $userVUG */
                    $userVUG = $em->getRepository(VanillaUserGroup::class)
                        ->findOneBy(['group' => $userVPG, 'user' => $user]);

                    if ($userVUG) {
                        $em->remove($userVUG);
                        $em->flush();
                    }

                    return true;
                } catch (Exception){
                    $this->log("User (".$user->getPeoplesoftId().") has already been removed from group: ".$userVPG->getVanillaGroupName());
                    return false;
                }
            } else {
                $this->log('[removeMemberFromGroup] Vanilla user id not found');
                throw new InvalidResourceException('Vanilla user id not found');
            }
        } else {
            $this->log('[removeMemberFromGroup] Group not found');
            throw new InvalidResourceException('Group not found');
        }
    }

    /**
     * Match the users of the programme to the members of the specific vanilla group
     * @param array  $peoples       Array of ojects of programme users
     * @param array  $groupMembers  Array of objects from the members of the vanilla group
     *
     * @return array
     */
    private function matchUsersWithVanillaMembers($peoples, $groupMembers){
        $cleanedMembers['included'] = [];
        $cleanedMembers['excluded'] = [];

        /** @var User $user */
        foreach ($peoples as $user){
            $key = array_search($user->getVanillaUserId(), array_column($groupMembers, 'userID'));

            $userArray = ['id' => $user->getId(), 'peoplesoft_id' => $user->getPeoplesoftId(), 'vanilla_user_id' => $user->getVanillaUserId(), 'firstname' => $user->getFirstname(), 'lastname' => $user->getLastname()];

            if ($key) {

                if (isset($groupMembers[$key]['isAdded'])) {
                    $userArray['isAdded'] = $groupMembers[$key]['isAdded'];
                    $userArray['isRemove'] = $groupMembers[$key]['isRemove'];
                }
                array_push($cleanedMembers['included'], $userArray);
            } else {
                if (isset($groupMembers[$key]['isAdded'])) {
                    $userArray['isAdded'] = $groupMembers[$key]['isAdded'];
                    $userArray['isRemove'] = $groupMembers[$key]['isRemove'];
                }
                array_push($cleanedMembers['excluded'], $userArray);
            }

        }

        return $cleanedMembers;
    }

    /**
     * List all people from a programme
     *
     * @param array $listOfPeople
     * @return array
     */
    private function getPeopleList(Programme $programme, $listOfPeople = null){

        if (!$listOfPeople){
            $listOfPeople = $this->peopleType;
        }

        /** @var Course $course */
        $courses = $programme->getCourses();

        /** @var array $peoples */
        $peoples = [];
        /** @var Course $course */
        foreach( $courses as $course ) {
            foreach ($listOfPeople as $getRole) {
                /** @var array $peopleList */
                $peopleList = $course->$getRole(true);

                if (count($peopleList) > 0){
                    $peoples = array_merge($peoples, $peopleList);
                }

            }
        }

        $peoples = array_unique($peoples,SORT_REGULAR);

        return $peoples;
    }

    /**
     * Function that will list user categorized by role
     * @param $programmeId
     * @return array
     */
    public function getUsersListByRole(Request $request, $programmeId){
        $programme = $this->getProgrammeObject($programmeId);
        return ['usersListByRole' => $this->getPeopleListByRole($programme)];
    }

    /**
     * List all people from a programme group by role
     *
     * @param array $listOfPeople
     * @return array
     */
    private function getPeopleListByRole(Programme $programme, $listOfPeople = null){

        $em = $this->entityManager;

        if (!$listOfPeople){
            $listOfPeople = $this->peopleType;
        }

        /** @var Course $course */
        $courses = $programme->getCourses();

        /** @var array $peoples */
        $peoples = [];

        $peoplesByRole = [];

        /** @var Course $course */
        foreach( $courses as $course ) {
            foreach ($listOfPeople as $getRole) {
                /** @var array $peopleList */
                $peopleList = $course->$getRole(true);

                if (count($peopleList) > 0){
                    $cleanedUser = [];
                    /** @var User $user */
                    foreach ($peopleList as $user){
                        $isFound = false;
                        /** @var User $userInPeople */
                        foreach ($peoples as $userInPeople){
                            if ($user->getId() == $userInPeople->getId()){
                                $isFound = true;
                                break;
                            }
                        }

                        if (!$isFound){

                                $membership = [];
                                $vanillaUserGroup = $em->getRepository(VanillaProgrammeGroup::class)
                                    ->createQueryBuilder('vpg')
                                    ->leftJoin('vpg.vanillaUserGroup', 'vug')
                                    ->where('vpg.programme = :programmeId and vug.user = :userId and vug.added = 1')
                                    ->select('vpg.name as groupName')
                                    ->setParameter('programmeId', $programme->getId())
                                    ->setParameter('userId',$user->getId())
                                    ->getQuery()
                                    ->getArrayResult();

                                foreach ($vanillaUserGroup as $myVanillaGroup)
                                    array_push($membership, $myVanillaGroup['groupName']);
                                $cleanedArr = [];
                                foreach($membership as $mem){

                                    if (preg_match("/~~~/", (string) $mem)){
                                        $tempMem = preg_split('/~~~/', (string) $mem);
                                        array_push($cleanedArr , $tempMem[1]);
                                    }else{
                                        array_push($cleanedArr , $mem);
                                    }

                            }

                            sort($cleanedArr);
                            $user->setMemberships($cleanedArr);
                            $user->setName();
                            array_push($cleanedUser, $user);
                        }

                    }
                    $peoples = array_merge($peoples, $peopleList);
                    if (count($cleanedUser) > 0){
                        $cleanedRoleName = "NA";
                        switch ($getRole){
                            case "getCoordination":
                                $cleanedRoleName = "Programme Coordination";
                                break;
                            case "getFaculty":
                                $cleanedRoleName = "Faculty";
                                break;
                            case "getContacts":
                                $cleanedRoleName = "Programme Contact";
                                break;
                            case "getDirectors":
                                $cleanedRoleName = "Programme Director";
                                break;
                            case "getStudents":
                                $cleanedRoleName = "Participant";
                                break;
                            case "getInseadTeam":
                                $cleanedRoleName = "INSEAD TEAM";
                                break;
                        }

                        $peoplesByRole[$cleanedRoleName] = $cleanedUser;
                    }
                }

            }
        }

        return $peoplesByRole;
    }

    /**
     * Create a discussion under a programme and send the request to Vanilla API
     * @param Request  $request       Request Object
     * @param integer  $programmeId   id of the programme
     *
     * @throws
     *
     * @return mixed/array
     */
    public function createDiscussion(Request $request, $programmeId){
        $em = $this->entityManager;

        $data = $this->loadDataFromRequest( $request, 'name,groupId,description' );

        if (!$data['name'] || !$data['groupId'] || !$data['description']){
            throw new InvalidResourceException('Missing data in request');
        }

        $groupId = $data['groupId'];

        $discussionName = trim((string) $data['name']);
        if (strlen($discussionName) > 0) {
            $this->cleanDiscussionData($discussionName);
        } else {
            throw new InvalidResourceException('Missing data in request');
        }

        $description = $data['description'];
        if (strlen((string) $description) > 0) {
            $this->cleanDiscussionData($description);
        } else {
            throw new InvalidResourceException('Missing data in request');
        }

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);

        if ($programme){
            $query  = $em->createQuery(
                'SELECT vpg FROM Insead\MIMBundle\Entity\VanillaProgrammeGroup vpg
            WHERE vpg.programme = :programme_id and vpg.vanillaGroupId = :vanillaGroupId'
            )
                ->setParameter('programme_id', $programmeId)
                ->setParameter('vanillaGroupId', $groupId);

            $group = $query->getResult();
            if (count($group) > 0){
                $category_search = "Social Groups";
                if ($this->default_category){
                    $category_search = $this->default_category;
                }

                $categoryObject = $this->vanillaCategory->search($category_search);
                if ($categoryObject){
                    $categoryObject = json_decode((string) $categoryObject, true);
                    if (count($categoryObject) > 0){
                        $categoryObject = $categoryObject[0];
                        $categoryID     = $categoryObject['categoryID'];
                        $discussionInfo = ["name" => $programme->getCode().'~'.$discussionName, "body" => $description, "format" => "wysiwyg", "categoryID"=> $categoryID, "closed" => false, "sink" => false, "pinned" => false, "pinLocation" => "category", "groupID" => $groupId];


                        $response = $this->vanillaDiscussion->create($discussionInfo);

                        /** @var array $response */
                        $response = json_decode((string) $response, true);

                        /** @var VanillaProgrammeDiscussion $vanillaProgrammeDiscussion */
                        $vanillaProgrammeDiscussion = new VanillaProgrammeDiscussion();
                        $vanillaProgrammeDiscussion->setProgramme($programme);
                        $vanillaProgrammeDiscussion->setName($discussionName);
                        $vanillaProgrammeDiscussion->setVanillaDiscussionId($response['discussionID']);
                        $vanillaProgrammeDiscussion->setUrl($response['url']);
                        $vanillaProgrammeDiscussion->setClosed(false);
                        $vanillaProgrammeDiscussion->setDescription($description);
                        $vanillaProgrammeDiscussion->setGroupId($groupId);
                        $responseSave = $this->createRecord(self::$ENTITY_VPD, $vanillaProgrammeDiscussion);
                        $em->flush();

                        if($responseSave) {
                            return $this->listDiscussion($request, $programmeId);
                        } else {
                            throw new InvalidResourceException('Unable to save the discussion');
                        }
                    } else {
                        throw new InvalidResourceException('Unable to get Category');
                    }
                } else {
                    throw new InvalidResourceException('Unable to get Category');
                }
            } else {
                throw new InvalidResourceException('Group not found');
            }
        } else {
            throw new InvalidResourceException('Programme not found');
        }
    }

    /**
     * List all discussion under a programme and send the request to Vanilla API
     * @param Request  $request       Request Object
     * @param integer  $programmeId   id of the programme
     *
     * @throws
     *
     * @return array
     */
    public function listDiscussion(Request $request, $programmeId){
        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);

        if ($programme){
            $query  = $em->createQuery(
                'SELECT vpd FROM Insead\MIMBundle\Entity\VanillaProgrammeDiscussion vpd
            WHERE vpd.programme = :programme_id and vpd.vanillaDiscussionId IS NOT NULL'
            )
            ->setParameter('programme_id', $programmeId);

            $discussions = $query->getResult();

            $discussionList = [];

            /** @var VanillaProgrammeDiscussion $discussion */
            foreach ($discussions  as $discussion){
                $vanillaDiscussionDetails = $this->getDiscussionByID($programmeId, $discussion->getVanillaDiscussionId());
                if (isset($vanillaDiscussionDetails['discussionID']))
                    array_push($discussionList,$vanillaDiscussionDetails);
            }

            return ['vanillaDiscussions' => $discussionList];
        } else {
            throw new InvalidResourceException('Programme not found');
        }
    }

    /**
     * Function that will list all discussion by group ID
     *
     * @param $groupId
     * @return array
     */
    public function listDiscussionByGroupId($groupId){
        $em = $this->entityManager;

        $VPD = $em->getRepository(VanillaProgrammeDiscussion::class)
            ->createQueryBuilder('vpd')
            ->where('vpd.groupId = :groupId')
            ->select('vpd.name, vpd.description, vpd.url')
            ->setParameter('groupId', $groupId)
            ->getQuery()
            ->getResult();

        return $VPD;
    }

    /**
     * Function to delete a discussion
     *
     * @param $programmeId
     * @param $discussionId
     * @return array
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeDiscussion(Request $request, $programmeId, $discussionId){
        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);

        /** @var VanillaProgrammeDiscussion $VPD */
        $VPD = $em->getRepository(VanillaProgrammeDiscussion::class)
            ->findOneBy(['vanillaDiscussionId' => $discussionId, 'programme' => $programme]);

        if ($VPD){
            try {
                $this->vanillaDiscussion->remove($discussionId);
                $em->remove($VPD);
                $em->flush();
                return $this->listDiscussion($request, $programmeId);
            } catch (Exception){
                throw new InvalidResourceException('Unable to remove discussion ID('.$discussionId.') from Vanilla API');
            }
        } else {
            throw new InvalidResourceException('Unable to find discussion to delete');
        }
    }

    /**
     * Handler to close a vanilla discussion
     *
     * @param $programmeId
     * @param $discussionId
     * @return array
     * @throws InvalidResourceException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function closeDiscussion(Request $request, $programmeId, $discussionId){
        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);

        /** @var VanillaProgrammeDiscussion $VPD */
        $VPD = $em->getRepository(VanillaProgrammeDiscussion::class)
            ->findOneBy(['vanillaDiscussionId' => $discussionId, 'programme' => $programme]);

        if ($VPD){
            try {
                $this->vanillaDiscussion->update($discussionId,['closed' => true, 'sink' => true]);
                $VPD->setClosed(true);
                $em->persist($VPD);
                $em->flush();
                return $this->listDiscussion($request, $programmeId);
            } catch (Exception){
                throw new InvalidResourceException('Unable to close discussion ID('.$discussionId.') from Vanilla API');
            }
        } else {
            throw new InvalidResourceException('Unable to find discussion to close');
        }
    }

    /**
     * Handler to re-open a vanilla discussion
     *
     * @param $request
     * @param $programmeId
     * @param $discussionId
     * @return array
     * @throws InvalidResourceException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function reOpenDiscussion($request, $programmeId, $discussionId){
        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);

        /** @var VanillaProgrammeDiscussion $VPD */
        $VPD = $em->getRepository(VanillaProgrammeDiscussion::class)
            ->findOneBy(['vanillaDiscussionId' => $discussionId, 'programme' => $programme]);

        if ($VPD){
            try {
                $this->vanillaDiscussion->update($discussionId,['closed' => false, 'sink' => false]);
                $VPD->setClosed(false);
                $em->persist($VPD);
                $em->flush();
                $this->log("Discussion successfully reOpened: ".$discussionId." programme id: ".$programmeId);
                return $this->listDiscussion($request, $programmeId);
            } catch (Exception){
                throw new InvalidResourceException('Unable to reOpen discussion ID('.$discussionId.') from Vanilla API');
            }
        } else {
            throw new InvalidResourceException('Unable to find discussion to reOpen');
        }
    }

    /**
     * Handler to update the discussion details
     *
     * @param $programmeId
     * @param $discussionId
     * @return array
     * @throws InvalidResourceException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function updateDiscussion(Request $request, $programmeId, $discussionId){
        $em = $this->entityManager;

        $data = $this->loadDataFromRequest( $request, 'name,groupId,description' );

        if (!$data['name'] || !$data['groupId'] || !$data['description']){
            throw new InvalidResourceException('Missing data in request');
        }

        $groupId        = $data['groupId'];

        $discussionName = trim((string) $data['name']);
        if (strlen($discussionName) > 0) {
            $this->cleanDiscussionData($discussionName);
        } else {
            throw new InvalidResourceException('Missing data in request');
        }

        $description = $data['description'];
        if (strlen((string) $description) > 0) {
            $this->cleanDiscussionData($description);
        } else {
            throw new InvalidResourceException('Missing data in request');
        }

        $programme = $this->getProgrammeObject($programmeId);

        /** @var VanillaProgrammeDiscussion $VPD */
        $VPD = $em->getRepository(VanillaProgrammeDiscussion::class)
            ->findOneBy(['vanillaDiscussionId' => $discussionId, 'programme' => $programme]);

        if ($VPD){
            try {
                $discussionInfo = ["name" => $programme->getCode().'~'.$discussionName, "body" => $description, "groupID" => $groupId, "format" => "wysiwyg"];

                $this->vanillaDiscussion->update($discussionId,$discussionInfo);

                $VPD->setName($discussionName);
                $VPD->setDescription($description);
                $VPD->setGroupId($groupId);
                $em->persist($VPD);
                $em->flush();
                return $this->listDiscussion($request, $programmeId);
            } catch (Exception){
                throw new InvalidResourceException('Unable to update discussion ID('.$discussionId.') from Vanilla API');
            }
        } else {
            throw new InvalidResourceException('Unable to find discussion to update');
        }
    }

    /**
     * Handles the updating of vanilla group details
     *
     * @param $programmeId
     * @param $groupId
     * @return array
     * @throws InvalidResourceException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function updateGroup(Request $request, $programmeId, $groupId){
        $em = $this->entityManager;

        $data = $this->loadDataFromRequest( $request, 'name,description' );

        $preferredVanillaGroupName = $this->env . '~' . $programmeId . '~~~' . trim((string) $data['name']);

        $groupInfo = ["name" => $preferredVanillaGroupName, "description" => $data['description']];

        /** @var VanillaProgrammeGroup $vanillaProgrammeGroup */
        $vanillaProgrammeGroup = $em->getRepository(VanillaProgrammeGroup::class)
            ->findOneBy(['vanillaGroupId' => $groupId]);

        if ($vanillaProgrammeGroup) {
            $this->vanillaGroup->update($groupId, $groupInfo);
            $vanillaProgrammeGroup->setVanillaGroupName(trim((string) $data['name']));
            $vanillaProgrammeGroup->setVanillaGroupDescription(trim((string) $data['description']));
            $em->persist($vanillaProgrammeGroup);
            $em->flush();
        } else {
            throw new InvalidResourceException('Unable to find group: '.$groupId);
        }

        return $this->getForumGroupList($request, $programmeId);
    }

    /**
     * Fetch the discussion details
     *
     * @param String $discussionID
     * @param String $programmeId
     * @return array
     */
    private function getDiscussionByID($programmeId, $discussionID){
        $em = $this->entityManager;

        /** @var VanillaProgrammeDiscussion $discussionObject */
        $discussionObject = $em->getRepository(VanillaProgrammeDiscussion::class)
            ->findOneBy(['vanillaDiscussionId' => $discussionID]);

        if ($discussionObject) {
            /** @var Programme $programme */
            $programme = $this->getProgrammeObject($programmeId);

            $peoples = $this->getPeopleList($programme);

            /** @var VanillaProgrammeGroup $vanillaGroup */
            $vanillaGroup = $em
                ->getRepository(VanillaProgrammeGroup::class)
                ->findOneBy(['programme' => $programme, 'vanillaGroupId' => $discussionObject->getGroupId()]);

            return ['discussionID' => $discussionObject->getVanillaDiscussionId(), 'name' => $discussionObject->getName(), 'description' => $discussionObject->getDescription(), 'isClosed'  => $discussionObject->getClosed(), 'group' => $this->getGroupDetails($programmeId, $peoples, $vanillaGroup)];
        } else {
            return [];
        }
    }

    /**
     * This function will be called periodically that will queue the user to add to everyone group if exists
     *
     * @throws
     * @return array
     */
    public function addUserToEveryone(Request $request)
    {
        // GET:                              300 requests per 1 minute, per IP
        // POST / PUT / PATCH / DELETE:      20 requests per 1 minute, per IP
        $em = $this->entityManager;

        /** @var array $programmes */
        $initialGroups = $em->getRepository(VanillaProgrammeGroup::class)
            ->findBy(['initial' => true]);

        $usersToProcess = [];
        /** @var VanillaProgrammeGroup $group */
        foreach ($initialGroups as $group){
            $programme = $group->getProgramme();
            $programme->setOverriderReadonly(true);

            if ($programme->getWithDiscussions()) {
                if ($programme->checkIfLive() || $programme->checkIfPending()) {

                    $vanillaUsersGroupList = $em->getRepository(User::class)
                        ->createQueryBuilder('u')
                        ->join('u.vanillaUserGroup', 'vug')
                        ->where('vug.group = :groupId and u.vanillaUserId IS NOT NULL')
                        ->setParameter('groupId',$group->getId())
                        ->getQuery()
                        ->getResult();

                    $people = $this->getPeopleList($programme);

                    $usersNotInEveryoneGroup = [];
                    /** @var User $user */
                    foreach($people as $user){

                        // process only user with vanilla id
                        if ($user->getVanillaUserId()) {
                            /** @var bool $isFoundOnVanillaUsersEveryoneGroup */
                            $isFoundOnVanillaUsersEveryoneGroup = false;

                            /** @var User $vanillaUser */
                            foreach ($vanillaUsersGroupList as $vanillaUser) {
                                if ($vanillaUser->getPeoplesoftId() == $user->getPeoplesoftId()) {
                                    $isFoundOnVanillaUsersEveryoneGroup = true;
                                    break;
                                }
                            }

                            if (!$isFoundOnVanillaUsersEveryoneGroup)
                                array_push($usersNotInEveryoneGroup, $user);
                        }
                    }

                    if (count($usersNotInEveryoneGroup) > 0) {
                        array_push($usersToProcess, $usersNotInEveryoneGroup);

                        /** @var User $newUser */
                        foreach ($usersNotInEveryoneGroup as $newUser) {
                            $this->mapUserGroup($group->getVanillaGroupId(), $newUser->getVanillaUserId(), false, false);
                        }
                    }
                }
            }
        }

        return $usersToProcess;
    }

    /**
     * Add user to queue for modular groups
     *
     * @throws InvalidResourceException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addUserToQueueByModular(Request $request){
        $em = $this->entityManager;

        /** @var array $programmes */
        $allProgrammeGroups = $em->getRepository(VanillaProgrammeGroup::class)
            ->createQueryBuilder('vpg')
            ->where('vpg.course IS NOT NULL')
            ->getQuery()
            ->getResult();

        /** @var VanillaProgrammeGroup $vanillaProgrammeGroup */
        foreach($allProgrammeGroups as $vanillaProgrammeGroup) {
            $programme = $vanillaProgrammeGroup->getProgramme();
            $programme->setOverriderReadonly(true);

            $usersInGroup = [];
            /** @var VanillaUserGroup $userInGroup */
            foreach($vanillaProgrammeGroup->getvanillaUserGroup() as $userInGroup){
                array_push($usersInGroup,$userInGroup->getUser()->getId());
            }

            /** @var Course $course */
            $course = $vanillaProgrammeGroup->getCourse();

            if(count($usersInGroup) > 0) {

                /** @var array $programmeUser */
                $programmeUser = $em->getRepository(CourseSubscription::class)
                    ->createQueryBuilder('cs')
                    ->andWhere('cs.user NOT IN (:users)')
                    ->andWhere('cs.programme = :programme')
                    ->andWhere('cs.course = :course')
                    ->setParameter('course', $course)
                    ->setParameter('programme', $programme)
                    ->setParameter('users',$usersInGroup)
                    ->getQuery()
                    ->getResult();

                /** @var CourseSubscription $cs */
                foreach ($programmeUser as $cs){
                    if ($cs->getUser()->getVanillaUserId()) {
                        $this->addMemberToGroupQueue($request, $course->getProgramme()->getId(), $vanillaProgrammeGroup->getVanillaGroupId(), $cs->getUser()->getVanillaUserId());
                        $this->log("Added to modular group: " . $cs->getUser()->getVanillaUserId() . " with group id: " . $vanillaProgrammeGroup->getVanillaGroupId());
                    }
                }

            } else {
                $this->addMemberToQueueByCourse( $request, $course, $vanillaProgrammeGroup->getVanillaGroupId());
            }

        }
    }

    /**
     * This function will be called periodically that will add the user to a member in vanilla group api
     * If there is a conversation to create it will prioritise to create the conversation
     *
     * @throws NotSupported
     */
    public function processPendingUser(Request $request){
        // GET:                              300 requests per 1 minute, per IP
        // POST / PUT / PATCH / DELETE:      20 requests per 1 minute, per IP

        $listOfPendingUser_all = $this->listOfPendingUser($request);
        if (count($listOfPendingUser_all) > 5) {
            $listOfPendingUser_all = array_slice($listOfPendingUser_all, 0, 5);
        }

        $listOfPendingUser = $listOfPendingUser_all;
        /** @var VanillaUserGroup $vanillaUserGroup */
        foreach($listOfPendingUser as $vanillaUserGroup){
            /** @var VanillaProgrammeGroup $group */
            $group = $vanillaUserGroup->getGroup();

            /** @var User $user */
            $user = $vanillaUserGroup->getUser();

            /** @var Programme $programme */
            $programme = $group->getProgramme();
            $programme->setOverriderReadonly(true);

            try {
                $this->addMemberToGroup($request, $programme->getId(), $group->getVanillaGroupId(), $user->getVanillaUserId());
                $this->log('Added successfully to group: '.$group->getvanillaGroupName().' peopleSoft: '.$user->getPeoplesoftId());
            } catch(\Exception){
                $this->log('Unable to Add to group: '.$group->getVanillaGroupName().' peopleSoft: '.$user->getPeoplesoftId());
            }
        }
    }

    /**
     * Function that will return list of pending user to add in vanilla group
     *
     *
     * @return array
     * @throws NotSupported
     */
    public function listOfPendingUser(Request $request)
    {
        $em = $this->entityManager;

        $listOfPendingUser = $em->getRepository(VanillaUserGroup::class)
            ->createQueryBuilder('vug')
            ->join('vug.user', 'u')
            ->where('vug.added = 0 and u.vanillaUserId IS NOT NULL')
            ->setMaxResults($this->vanillaAPILimitRequest)
            ->getQuery()
            ->getResult();

        return $listOfPendingUser;
    }

    /**
     * Function that will update the vanilla user to force update the isAdded to true
     *
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateVanillaUserIsAdded(Request $request)
    {
        $data = $this->loadDataFromRequest( $request, 'groupId,vanillaId' );

        if ($data['groupId'] && $data['vanillaId']) {
            if (trim((string) $data['groupId']) != "" && trim((string) $data['vanillaId']) != "") {
                $vanillaGroupId = $data['groupId'];
                $vanillaUserId = $data['vanillaId'];

                $em = $this->entityManager;

                /** @var VanillaProgrammeGroup $vanillaGroup */
                $vanillaGroup = $em
                    ->getRepository(VanillaProgrammeGroup::class)
                    ->findOneBy(['vanillaGroupId' => $vanillaGroupId]);

                if ($vanillaGroup) {
                    /** @var User $user */
                    $user = $em->getRepository(User::class)
                        ->findOneBy(['vanillaUserId' => $vanillaUserId]);

                    if ($user) {
                        /** @var VanillaUserGroup $vanillaUserGroup */
                        $vanillaUserGroup = $em->getRepository(VanillaUserGroup::class)
                            ->findOneBy(['user' => $user, 'group' => $vanillaGroup]);

                        if ($vanillaUserGroup) {
                            $vanillaUserGroup->setAdded(true);

                            $em->persist($vanillaUserGroup);
                            $em->flush($vanillaUserGroup);

                            return $vanillaUserGroup;
                        } else {
                            return ['Vanilla user not found'];
                        }
                    } else {
                        return ['User not found'];
                    }
                } else {
                    return ['Group not found'];
                }
            } else {
                return ['All fields should have a value'];
            }
        } else {
            return ['All fields are required'];
        }
    }

    /**
     * This function will be called periodically that will remove the user to a member in vanilla group api
     */
    public function processRemovingUser(Request $request){
        // GET:                              300 requests per 1 minute, per IP
        // POST / PUT / PATCH / DELETE:      20 requests per 1 minute, per IP
        $em = $this->entityManager;

        $listOfPendingUser = $em->getRepository(VanillaUserGroup::class)
            ->createQueryBuilder('vug')
            ->where('vug.remove = 1')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        /** @var VanillaUserGroup $vanillaUserGroup */
        foreach($listOfPendingUser as $vanillaUserGroup){
            /** @var VanillaProgrammeGroup $group */
            $group = $vanillaUserGroup->getGroup();

            /** @var User $user */
            $user = $vanillaUserGroup->getUser();

            /** @var Programme $programme */
            $programme = $group->getProgramme();
            $programme->setOverriderReadonly(true);

            try {
                $this->removeMemberFromGroup($request, $programme->getId(), $group->getVanillaGroupId(), $user->getVanillaUserId());
                $this->log('Removed successfully to group: '.$group->getvanillaGroupName().' peopleSoft: '.$user->getPeoplesoftId());
            } catch(\Exception){
                $this->log('Unable to Remove to group: '.$group->getVanillaGroupName().' peopleSoft: '.$user->getPeoplesoftId());
            }
        }
    }

    /**
     * Handler to get vanilla discussion url, used by web and iOS
     *
     * @param $programmeId
     * @return array
     */
    public function getURL(Request $request, $programmeId){
        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);

        $discussionList = $em->getRepository(VanillaProgrammeDiscussion::class)
            ->findBy(['programme' => $programme]);
        $searchString = $this->env."~".$programmeId;

        $discussionPostFixURL = 'Search='.$searchString.'&env='.$this->env.'&programme='.$programme->getCode().'~';
        $MessageBookmarkPostFixURL = 'env='.$this->env.'&programme='.$programme->getCode().'~';
        if ($discussionList) {
            if (count($discussionList) > 1) {
                $discussionURL = $this->vanilla_base_url.'/groups/browse/search';
            } else {
                /** @var VanillaProgrammeDiscussion $discussionObject */
                $discussionObject = $discussionList[0];
                $discussionURL = $discussionObject->getUrl().'/groups/browse/search';
                $discussionURL = str_replace("https://inseadpilot.vanillacommunity.com",$this->vanilla_base_url,$discussionURL);
            }
        } else {
            $discussionURL = $this->vanilla_base_url.'/groups/browse/search';
        }

        return ['discussionURL' => $discussionURL.'?'.$discussionPostFixURL, 'conversationURL' => $this->vanilla_base_url.'/messages/inbox/?'.$MessageBookmarkPostFixURL, 'favoritesURL' => $this->vanilla_base_url.'/discussions/bookmarked/?'.$MessageBookmarkPostFixURL];
    }

    /**
     * Function that will add to queue for the creation of vanilla conversation
     * @param $programmeId
     * @return array
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createConversation(Request $request, $programmeId)
    {
        $em = $this->entityManager;
        $data = $this->loadDataFromRequest($request, 'vanillaUsers,peopleSoft');

        $programme = $this->getProgrammeObject($programmeId);
        $confirmedVanillaUser = [];

        if (isset($data['vanillaUsers']) && isset($data['peopleSoft'])){
            $peopleSoftId = $data['peopleSoft'];
            $vanillaUsers = $data['vanillaUsers'];

            if ($programme){
                /** @var integer $vanillaUserId */
                foreach ($vanillaUsers as $vanillaUserId){
                    /** @var User $user */
                    $user = $em->getRepository(User::class)
                        ->findOneBy(['vanillaUserId' => $vanillaUserId]);

                    if ($user) {
                        /** @var CourseSubscription $programmeUser */
                        $programmeUser = $em->getRepository(CourseSubscription::class)
                            ->findBy(['user' => $user, 'programme' => $programme]);

                        if ($programmeUser){
                            array_push($confirmedVanillaUser, $vanillaUserId);
                        } else {
                            $this->log("[Conversation Create] User not in programme");
                            throw new InvalidResourceException('User not in programme');
                        }
                    } else {
                        $this->log("[Conversation Create] User not found");
                        throw new InvalidResourceException('User not found');
                    }
                }

                /** @var User $user */
                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['peoplesoft_id' => $peopleSoftId]);

                array_push($confirmedVanillaUser, $user->getVanillaUserId());

                $userConversationList = $em->getRepository(VanillaConversation::class)
                    ->findBy(['user' => $user, 'programme' => $programme]);

                if (count($userConversationList) >= 5){
                    $this->log("[Conversation Create] Conversation limit error");
                    return ['error' => 'Conversation limit has been reached'];
                } else {
                    /** @var VanillaConversation $vanillaConversationObject */
                    foreach($userConversationList as $vanillaConversationObject){
                        if (!$vanillaConversationObject->processed()){
                            $this->log("[Conversation Create] There are pending conversation to create");
                            throw new InvalidResourceException('There are pending conversation to create');
                        }
                    }

                    $vanillaConversation = new VanillaConversation();
                    $vanillaConversation->setUser($user);
                    $vanillaConversation->setProgramme($programme);
                    $vanillaConversation->setProcessed(false);
                    $vanillaConversation->setUserList(implode(",",$confirmedVanillaUser));
                    $this->createRecord(self::$ENTITY_VC, $vanillaConversation);
                    $em->persist($vanillaConversation);
                    $em->flush();
                }

                return ['success'];
            } else {
                $this->log("[Conversation Create] Programme not found");
                throw new InvalidResourceException('Programme not found');
            }
        } else {
            $this->log("[Conversation Create] Missing parameters in your request");
            throw new InvalidResourceException('Missing parameters in your request');
        }
    }

    /**
     * This function will be called periodically that will create conversation to vanilla with vanilla api
     *
     * @return array|object[]
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function processPendingConversation(Request $request){
        // GET:                              300 requests per 1 minute, per IP
        // POST / PUT / PATCH / DELETE:      20 requests per 1 minute, per IP
        $em = $this->entityManager;

        $userConversationList = $em->getRepository(VanillaConversation::class)
            ->findBy(['processed' => false],null,$this->conversationLimitRequest);

        /** @var VanillaConversation $vanillaConversationToCreate */
        foreach ($userConversationList as $vanillaConversationToCreate){
            try {
                $response = $this->vanillaConversationAPI->create($vanillaConversationToCreate->getUser()->getPeoplesoftId(), $vanillaConversationToCreate->getUserList());
                $response = json_decode((string) $response, true);
                $conversationID = $response['conversationID'];
                $vanillaConversationToCreate->setProcessed(true);
                $vanillaConversationToCreate->setConversationID($conversationID);
                $em->persist($vanillaConversationToCreate);

                // ask API to leave the conversation
                $this->vanillaConversationAPI->leave($conversationID);
            } catch (VanillaGenericException) {
                $this->log("There was an error creating conversation for User: ".$vanillaConversationToCreate->getUser()->getPeoplesoftId()." Programme: ".$vanillaConversationToCreate->getProgramme()->getName());
            }
        }

        $em->flush();

        return $userConversationList;
    }

    public function processCreateGroupByModule(Request $request){
        $em = $this->entityManager;

        // GET:                              300 requests per 1 minute, per IP
        // POST / PUT / PATCH / DELETE:      20 requests per 1 minute, per IP

        /** @var array $programmesWithAtLeastOneGroup */
        $programmesWithAtLeastOneGroup = $em
            ->getRepository(Programme::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.programmegroups', 'v')
            ->where('p.withDiscussions = 1')
            ->groupBy('p.id')
            ->having('COUNT(v.id) > 0')
            ->setMaxResults($this->vanillaAPILimitRequest)
            ->getQuery()
            ->getResult();

        $numberOfRequestInitiated = 0;
        $numberOfMaximumRequest = $this->vanillaAPILimitRequest;
        /** @var Programme $programme */
        foreach ($programmesWithAtLeastOneGroup as $programme){
            $programme->setOverriderReadonly(true);

            /** @var array $groups */
            $groups = $this->getForumGroupList($request,$programme->getId());
            $groups = $groups['vanillaGroup'];

            if (count($groups) < 1){
                $this->log("Skipping programme [".$programme->getId()."] because no initial group yet");
            } else {
                /** @var Course $course */
                foreach ($programme->getCourses() as $course) {
                    if ($numberOfRequestInitiated == $numberOfMaximumRequest) {
                        $this->log("Exiting create group function because limit has been reached");
                        break;
                    }

                    $vanillaGroup = $em->getRepository(VanillaProgrammeGroup::class)
                        ->findBy(['course' => $course]);

                    if (!$vanillaGroup) {
                        $this->log("Creating vanilla group for course: " . $course->getName()." in Programme: ".$programme->getId());

                        $vanillaGroupName = $this->env.'~'.$programme->getId().'~~~'.$course->getName();
                        $groupDescription = $programme->getCode().'~'.$course->getName();
                        $groupInfo = ["name" => $vanillaGroupName, "description" => $groupDescription, "format" => "text", "iconUrl" => "", "bannerUrl" => "", "privacy" => "secret"];

                        $response = json_decode((string) $this->vanillaGroup->create($groupInfo));
                        $vanillaGroupId = $response->groupID;

                        /** @var VanillaProgrammeGroup $vanillaProgrammeGroup */
                        $vanillaProgrammeGroup = new VanillaProgrammeGroup();
                        $vanillaProgrammeGroup->setProgramme($programme);
                        $vanillaProgrammeGroup->setVanillaGroupName($vanillaGroupName);
                        $vanillaProgrammeGroup->setVanillaGroupId($vanillaGroupId);
                        $vanillaProgrammeGroup->setVanillaGroupDescription($groupDescription);
                        $vanillaProgrammeGroup->setInitial(false);
                        $vanillaProgrammeGroup->setCourse($course);
                        $this->createRecord(self::$ENTITY_VPG, $vanillaProgrammeGroup);
                        $em->persist($vanillaProgrammeGroup);
                        $em->flush($vanillaProgrammeGroup);

                        $this->addMemberToQueueByCourse($request,$course,$vanillaGroupId);

                    }
                }
            }
        }
    }

    /**
     * @param Course $course
     * @param $vanillaGroupId
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addMemberToQueueByCourse(Request $request, $course, $vanillaGroupId){
        /** @var string $userType */
        foreach ($this->peopleType as $userType){
            $usersList = $course->$userType(true);
            if($usersList) {
                /** @var User $user */
                foreach ($usersList as $user) {
                    if ($user->getVanillaUserId()) {
                        $this->addMemberToGroupQueue($request, $course->getProgramme()->getId(), $vanillaGroupId, $user->getVanillaUserId());
                        $this->log("Added to modular group: " . $user->getVanillaUserId() . " with group id: " . $vanillaGroupId);
                    }
                }
            }
        }
    }

    /**
     * Create programme object base by programme id
     * 
     * @param String $programmeId
     * @return Programme
     */
    private function getProgrammeObject($programmeId){
        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $em
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);
        $programme->setOverriderReadonly(true);

        return $programme;
    }

    /**
     * Return the people list by programme with role
     *
     * @param $programmeId
     * @return array
     */
    public function peopleListWIthRole(Request $request, $programmeId){
        /** @var Programme $programme */
        $programme = $this->getProgrammeObject($programmeId);

        $listOfPeopleByRole = $this->getPeopleListByRole($programme);

        return $listOfPeopleByRole;
    }

    private function cleanDiscussionData(&$stringObject) {
        $stringObject = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $stringObject);
        $stringObject = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $stringObject);
        $stringObject = preg_replace('#<link(.*?)>(.*?)</link>#is', '', $stringObject);

        //removing inline js events
        $stringObject = preg_replace("/([ ]on[a-zA-Z0-9_-]{1,}=\".*\")|([ ]on[a-zA-Z0-9_-]{1,}='.*')|([ ]on[a-zA-Z0-9_-]{1,}=.*[.].*)/","", $stringObject);

        //removing inline js
        $stringObject = preg_replace("/([ ]href.*=\".*javascript:.*\")|([ ]href.*='.*javascript:.*')|([ ]href.*=.*javascript:.*)/i","", $stringObject);
    }
}
