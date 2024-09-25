<?php

namespace esuite\MIMBundle\Service\Manager;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Entity\Administrator;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\Group;
use esuite\MIMBundle\Entity\ProgrammeUser;
use esuite\MIMBundle\Entity\Role;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\VanillaProgrammeGroup;
use esuite\MIMBundle\Entity\VanillaUserGroup;
use esuite\MIMBundle\Exception\BoxGenericException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Service\AIPService;
use Symfony\Component\HttpFoundation\Request;

class CoursePeopleManager extends Base
{
    /**
     * @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Course";

    /**
     * @var array()
     * List of all Roles
     */
    public static $ROLE_ENUM = [
        '0'  => 'coordinator',
        '1'  => 'faculty',
        '2'  => 'director',
        '3'  => 'student',
        '4'  => 'advisor',
        '5'  => 'manager',
        '6'  => 'contact',
        '7'  => 'consultant',
        '8'  => 'guest',
        '9'  => 'hidden',
        '10' => 'esuiteteam'
    ];

    public static $ROLE_ENUM_PLURAL = [
        '0'  => 'coordinators',
        '1'  => 'professors',
        '2'  => 'directors',
        '3'  => 'students',
        '4'  => 'advisors',
        '5'  => 'managers',
        '6'  => 'contacts',
        '7'  => 'consultants',
        '8'  => 'guests',
        '9'  => 'hidden',
        '10' => 'esuiteteam'
    ];


    public static $ADMIN_ROLES_ENUM = [
        '0'  => 'coordinator',
        '1'  => 'faculty',
        '2'  => 'director',
        '4'  => 'advisor',
        '5'  => 'manager',
        '6'  => 'contact',
        '7'  => 'consultant',
        '8'  => 'guest',
        '9'  => 'hidden',
        '10' => 'esuiteteam'
    ];

    public static $STUDENT_ROLES_ENUM = [
        '3' => 'student'
    ];

    public static $ADMIN_CONSTITUENT_TYPES = [
        'Staff',
        'Faculty',
        'esuite Contractor',
        'esuite Client',
        'esuite Coaches',
        'Alumni',
        'Past Participant'
    ];

    public static $STUDENT_CONSTITUENT_TYPES = [
        'Student',
        'Participant',
        'Alumni'
    ];

    protected $aip_enabled;

    protected $AIPService;

    protected $userProfileManager;

    public function loadServiceManager($config, AIPService $AIPService, UserProfileManager $userProfileManager)
    {

        $aip_config               = $config['aip_config'];
        $this->aip_enabled        = $aip_config['aip_enabled'];

        $this->AIPService         = $AIPService;

        $this->userProfileManager = $userProfileManager;
    }

    /**
     * Handler function to list all students & professors assigned to a Course
     *
     * @param $courseId
     *
     * @return array
     *
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    public function getAssignedPeople(Request $request, $courseId)
    {

        $this->log("Getting Users assigned for Course:" . $courseId);
        $course = $this->findById(self::$ENTITY_NAME, $courseId);

        // Get all users subscribed to this course
        $userIds = $course->getAllUsers();

        $students     = $this->getEnrichedProfiles($request, $userIds, 'students');
        $faculty      = $this->getEnrichedProfiles($request, $userIds, 'professors');
        $coordinators = $this->getEnrichedProfiles($request, $userIds, 'coordinators');
        $directors    = $this->getEnrichedProfiles($request, $userIds, 'directors');
        $contacts     = $this->getEnrichedProfiles($request, $userIds, 'contacts');
        $advisors     = $this->getEnrichedProfiles($request, $userIds, 'advisors');
        $managers     = $this->getEnrichedProfiles($request, $userIds, 'managers');
        $consultants  = $this->getEnrichedProfiles($request, $userIds, 'consultants');
        $guests       = $this->getEnrichedProfiles($request, $userIds, 'guests');
        $hidden       = $this->getEnrichedProfiles($request, $userIds, 'hidden');
        $esuiteteam   = $this->getEnrichedProfiles($request, $userIds, 'esuiteteam');

        return [
            'coordinators' => $coordinators,
            'students'     => $students,
            'professors'   => $faculty,
            'directors'    => $directors,
            'advisors'     => $advisors,
            'managers'     => $managers,
            'contacts'     => $contacts,
            'consultants'  => $consultants,
            'guests'       => $guests,
            'hidden'       => $hidden,
            'esuiteteam'   => $esuiteteam
        ];
    }

    /**
     * Add to Admin if user is not added to admin
     *
     * @param $isFaculty
     * @throws ORMException
     */
    private function addToAdmin(User $user, $isFaculty){
        /** @var Administrator $admin */
        $admin = $this->entityManager
            ->getRepository(Administrator::class)
            ->findOneBy( [ "peoplesoft_id" => $user->getPeoplesoftId() ] );

        if( $admin) {
            if( $admin->getFaculty() != $isFaculty ) {
                $admin->setFaculty($isFaculty);

                $this->entityManager->persist($admin);
            }
        } else {
            $admin = new Administrator();
            $admin->setPeoplesoftId( $user->getPeoplesoftId() );
            $admin->setFaculty($isFaculty);

            $this->entityManager->persist($admin);
        }
    }

    /**
     * Handler function to assign a list of users to a course
     *
     * @param $courseId
     *
     * @return array
     *
     * @throws BoxGenericException
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function assignUserToCourse(Request $request, $courseId)
    {

        $this->checkReadWriteAccess($request,$courseId);

        /** @var Course $course */
        $course       = $this->findById(self::$ENTITY_NAME, $courseId);
        $people       = $request->get('people');

        $invalidUsers = [];
        $unknownUsers = [];

        // If No users, then return error response
        if (count($people) <= 0) {
            throw new InvalidResourceException(['people' => 'At least one Peoplesoft ID should be entered.']);
        }

        // Sometimes, an array with empty string is sent
        if (count($people) == 1 && trim((string) $people[ 0 ]) == '') {
            throw new InvalidResourceException(['people' => 'At least one Peoplesoft ID should be entered.']);
        }

        $this->log("ASSIGNING USERS TO COURSE: " . $courseId . ' - ' . json_encode($people));

        // Find Role by name
        /** @var Role $role */
        $role = $this->entityManager
            ->getRepository(Role::class)
            ->findOneBy(['name' => self::$ROLE_ENUM[ $request->get('role') ]]);

        $em = $this->entityManager;
        foreach ($people as $person) {
            $isAdmin = FALSE;
            $isFaculty = false;

            /** @var User $user */
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['peoplesoft_id' => $person]);

            $this->log('Validate user: ' . $person);

            $usersConstituentTypeString = false;

            // create non-existent mim users
            if (!$user) {
                $this->log("User not in database getting info from AIP");
                try {
                    $body = $this->AIPService->getUserApi($person);
                    $this->userProfileManager->saveESBWithPayload($body);

                    /** @var User $user */
                    $user = $this->entityManager
                        ->getRepository(User::class)
                        ->findOneBy(['peoplesoft_id' => $person]);

                    $this->log("Psoft: " . $person . " has been checked in AIP. Refreshing user model");
                    if ($user) {
                        $this->log("Psoft: " . $person . " copying cache details to core");
                        $this->userProfileManager->copyToCoreProfile($user);
                        $this->entityManager->refresh($user);
                    } else {
                        $this->log("Psoft: " . $person . " not found in AIP");
                        array_push($unknownUsers, $person);
                        continue;
                    }
                } catch (\Exception $e) {
                    $this->log($e);
                    $this->log("Psoft: " . $person . " not found in AIP Exception error");
                    array_push($unknownUsers, $person);
                    continue;
                }
            } else {
                try {
                    if (!$user->getCacheProfile()) {
                        $this->log("Psoft: " . $user->getPeoplesoftId() . " not in cache. Calling AIP user.");
                        $body = $this->AIPService->getUserApi($user->getPeoplesoftId());
                        $this->userProfileManager->saveESBWithPayload($body);
                        $this->log("Psoft: " . $user->getPeoplesoftId() . " has been checked in AIP. Refreshing user model");
                        $this->entityManager->refresh($user);
                    }

                    if (!$user->getCoreProfile()) {
                        $this->log("Psoft: " . $user->getPeoplesoftId() . " not in core. Copying cache details");
                        $this->userProfileManager->copyToCoreProfile($user);
                    }

                    $usersConstituentTypeString = $user->getUserConstituentTypeString();
                } catch (\Exception) {
                    array_push($unknownUsers, $person);
                    continue;
                }
            }

            // If assigning as an Admin role, check if user has the right constituent_type
            if(array_key_exists($request->get('role'), self::$ADMIN_ROLES_ENUM)) {
                $this->log('User being assigned as an Admin role: ' . $request->get('role'));

                if ($usersConstituentTypeString) {
                    foreach ($usersConstituentTypeString as $cType) {
                        $this->log('Constituent_type :: ' . $cType['constituent_type']);
                        if (in_array($cType['constituent_type'], self::$ADMIN_CONSTITUENT_TYPES)) {
                            $this->log('User is Indeed an Admin!!');
                            $isAdmin = TRUE;
                        }

                        if ($cType['constituent_type'] === "Faculty") {
                            $isFaculty = true;
                            break;
                        }
                    }

                    if( $isAdmin ) {
                        $this->log("Removing code to auto add to admin");
                    }
                }

                if(!$isAdmin) {
                    // Ignore this user and continue
                    array_push($invalidUsers, $person);
                    continue;
                }
            }

            /** @var CourseSubscription $subscription */
            $subscription = $this->entityManager
                ->getRepository(CourseSubscription::class)
                ->findOneBy(['user' => $user->getId(), 'course' => $course->getId()]);

            if ($subscription) {
                //invalid, existing in course
                $this->log('USER ALREADY ASSIGNED TO COURSE: ' . $person);

                throw new InvalidResourceException(
                    ['people' => ['User [' . $person . '] has already been assigned to this course as ' . $subscription->getRole()->getName()]]
                );

            } else {
                //valid, not existing in course

                // persist user
                $em->persist($user);

                if ($user && $role) {
                    $subscription = new CourseSubscription();
                    $subscription->setCourse($course);
                    $subscription->setUser($user);
                    $subscription->setRole($role);
                    $subscription->setProgramme($course->getProgramme());

                    $course = $course->addUser($subscription);

                    $em->persist($course);

                    $this->log('ASSIGNED USER ' . $person . ' TO COURSE ' . $course->getId() . ' WITH ROLE ' . $role->getName());

                    // ADD USER TO DEFAULT COURSE GROUP
                    $courseDefaultGroupA = $this->entityManager
                        ->getRepository(Group::class)
                        ->findBy(['course_default' => TRUE, 'course' => $course->getId()]);

                    if (count($courseDefaultGroupA) > 0) {
                        // This Course has a default Group
                        $courseDefaultGroup = $courseDefaultGroupA[0];
                        $courseDefaultGroup = $courseDefaultGroup->addUser($user);
                        $em = $this->entityManager;
                        $em->persist($courseDefaultGroup);
                    }
                }
            }
        }

        $em->flush();

        try {
            // push notification
            $notify = $this->notify;
            $notify->setLogUuid($request);
            $notify->message($course, self::$ENTITY_NAME);
        } catch (\Exception $e) {
            $this->log("Notify error: " . $e);
        }

        if (count($invalidUsers) > 0) {
            $errorMsg = implode(', ', $invalidUsers);

            throw new InvalidResourceException(['people' => ['The following Peoplesoft Ids cannot be added as part of programme team: ' . $errorMsg]]);

        } else if (count($unknownUsers) > 0) {
            $errorMsg = implode(', ', $unknownUsers);

            throw new InvalidResourceException(['people' => ['The following Peoplesoft Ids does not have a match: ' . $errorMsg]]);
        }

        return $course->getAllUsers();
    }

    /**
     * Adding to course by selected list of people used in recycle
     *
     * @param $courseId
     * @param $roleID
     *
     * @return mixed
     *
     * @throws BoxGenericException
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function customAssignUserToCourse(Request $request, array $listOfPeople, $courseId, $roleID){
        if (count($listOfPeople) > 0) {
            $request->request->set("role", $roleID);
            $request->request->set("people", $listOfPeople);
            return $this->assignUserToCourse($request, $courseId);
        } else {
            return true;
        }
    }

    /**
     * Check if Course and User exists
     *
     * @param $course
     * @param $user
     * @throws InvalidResourceException
     */
    private function courseUserChecker($course, $user)
    {
        if(!$course) {
            $this->log('Course not found');
            throw new InvalidResourceException(['people' => ['Course not found']]);
        }

        if(!$user) {
            $this->log('User not found');
            throw new InvalidResourceException(['people' => ['User does not exists']]);
        }
    }

    /**
     * Handler function to get the current course subscription of a user in a given course
     *
     * @param $courseId
     * @param $peoplesoftId
     *
     * @return array
     *
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    public function getUserInfoInCourse(Request $request, $courseId, $peoplesoftId)
    {

        $this->checkReadWriteAccess($request,$courseId);

        $this->log('GETTING USER INFO ' . $peoplesoftId . ' IN COURSE: ' . $courseId);

        $em = $this->entityManager;

        /** @var Course $course */
        $course = $em->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        /** @var User $user */
        $user   = $em->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peoplesoftId]);

        $this->courseUserChecker($course, $user);

        /** @var CourseSubscription $subscription */
        $subscription = $this->entityManager
            ->getRepository(CourseSubscription::class)
            ->findOneBy(['user' => $user->getId(), 'course' => $course->getId()]);

        if(!$subscription) {
            $this->log('User is not found in the Course');
            throw new InvalidResourceException(['people' => ['User does not belong to the Course']]);
        }

        return ["programme_id" => $subscription->getProgramme()->getId(), "course_id" => $subscription->getCourse()->getId(), "peoplesoft_id" => $subscription->getUser()->getPeoplesoftId(), "role" => $subscription->getRole()->getName()];
    }

    /**
     * Handler function to change a user in a given course
     *
     * @param $courseId
     * @param $peoplesoftId
     *
     * @return array
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function changeUserInCourse(Request $request, $courseId, $peoplesoftId)
    {

        $scope  = $this->getCurrentUserScope($request);

        $this->checkReadWriteAccess($request,$courseId);

        $this->log('CHANGING USER ' . $peoplesoftId . ' IN COURSE: ' . $courseId);

        $em = $this->entityManager;

        /** @var Course $course */
        $course = $em->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        /** @var User $user */
        $user   = $em->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peoplesoftId]);

        $this->courseUserChecker($course, $user);

        $programme = $course->getProgramme();
        $programme->setRequestorId($user->getId());
        $programme->setRequestorScope($scope);

        $newUserPeoplesoftId = $request->get('user_psoftid');
        $newUserRole = $request->get('user_role');

        if( !is_null($newUserPeoplesoftId) && !is_null($newUserRole) ) {
            throw new InvalidResourceException(['people' => ['You are trying to change both User Peoplesoft Id and Role as the same action.']]);
        }

        if( is_null($newUserPeoplesoftId) && is_null($newUserRole) ) {
            throw new InvalidResourceException(['people' => ['No action to process']]);
        }

        /** @var CourseSubscription $subscription */
        $subscription = $this->entityManager
            ->getRepository(CourseSubscription::class)
            ->findOneBy(['user' => $user->getId(), 'course' => $course->getId()]);

        if(!$subscription) {
            $this->log('User is not found in the Course');
            throw new InvalidResourceException(['people' => ['User does not belong to the Course']]);
        }

        $currentRole = $subscription->getRole();

        //change role
        if( !is_null($newUserRole) ) {
            $this->log("Changing role of " . $user->getPeoplesoftId() . " for Course " . $course->getId() );

            //reassigning role as admin
            if(array_key_exists($newUserRole, self::$ADMIN_ROLES_ENUM)) {
                //check if user is currently an admin
                $currentAdmin = false;
                foreach( self::$ADMIN_ROLES_ENUM as $adminRoleIndex => $adminRole ) {
                    if( $currentRole->getName() === $adminRole ) {
                        $currentAdmin = true;
                        break;
                    }
                }

                if( !$currentAdmin ) {
                    $this->log('A student cannot be changed as part of Programme Team');
                    throw new InvalidResourceException(['people' => ['A student cannot be changed as part of Programme Team. Please un-enroll the user first.']]);
                }
            }

            // Find Role by name
            $newRole = $this->entityManager
                ->getRepository(Role::class)
                ->findOneBy(['name' => self::$ROLE_ENUM[ $newUserRole ]]);

            $subscription->setRole($newRole);
            $em->persist($subscription);
        }

        //change user within course
        if( !is_null($newUserPeoplesoftId) ) {
            $this->log("Changing user " . $user->getPeoplesoftId() . " for Course " . $course->getId() . " to " . $newUserPeoplesoftId );

            /** @var User $newUser */
            $newUser = $em->getRepository(User::class)
                ->findOneBy(['peoplesoft_id' => $newUserPeoplesoftId]);

            // create non-existent mim users
            if (!$newUser) {
                $this->log("User [".$newUserPeoplesoftId."] does not exists");
                throw new InvalidResourceException(['people' => ["User does not exists"]]);
            }

            $isAdmin = FALSE;
            $isFaculty = false;

            $usersConstituentTypeString = $user->getUserConstituentTypeString();
            if ($usersConstituentTypeString) {
                foreach ($usersConstituentTypeString as $cType) {
                    $this->log('Constituent_type :: ' . $cType['constituent_type']);
                    if (in_array($cType['constituent_type'], self::$ADMIN_CONSTITUENT_TYPES)) {
                        $this->log('User is Indeed an Admin!!');
                        $isAdmin = TRUE;
                    }

                    if ($cType['constituent_type'] === "Faculty") {
                        $isFaculty = true;
                        break;
                    }
                }

                if( $isAdmin ) {
                    $this->log("Removing code to auto add to admin");
                }
            }

            /** @var CourseSubscription $subscription */
            $newSubscription = $this->entityManager
                ->getRepository(CourseSubscription::class)
                ->findOneBy(['user' => $newUser->getId(), 'course' => $course->getId()]);

            if( $newSubscription ) {
                $this->log('USER ALREADY ASSIGNED TO COURSE: ' . $newUser->getPeoplesoftId());

                throw new InvalidResourceException(
                    ['people' => ['User [' . $newUser->getPeoplesoftId() . '] has already been assigned to this course as ' . $newSubscription->getRole()->getName()]]
                );
            }

            $currentAdmin = false;
            foreach( self::$ADMIN_ROLES_ENUM as $adminRoleIndex => $adminRole ) {
                if( $currentRole->getName() === $adminRole ) {
                    $currentAdmin = true;
                    break;
                }
            }

            // If current role of the user is an admin, check if user has the right constituent_type
            if($currentAdmin) {
                if(!$isAdmin) {
                    $this->log('The User ' . $newUserPeoplesoftId . ' cannot be added as part of programme team');
                    throw new InvalidResourceException(['people' => ['The User [' . $newUserPeoplesoftId . '] cannot be added as part of programme team']]);
                }
            }

            //replace session host information
            $sessions = $course->getSessions();
            /** @var Session $session */
            foreach( $sessions as $session ) {
                if( in_array($user->getPeoplesoftId(),$session->getProfessorList()) ) {
                    $this->log('Replacing Session host info for User [' . $user->getPeoplesoftId() . '] to [' . $newUser->getPeoplesoftId() . ']');
                    $session->addProfessor($newUser);
                    $session->removeProfessor($user);
                    $em->persist($session);
                }
            }

            //replace group membership
            /** @var Group $courseGroups */
            $courseGroups = $this->entityManager
                ->getRepository(Group::class)
                ->findBy(['course' => $course->getId()]);

            /** @var Group $courseGroup */
            foreach($courseGroups as $courseGroup) {
                if( in_array($user->getPeoplesoftId(),$courseGroup->getUsersList()) ) {
                    $this->log('Replacing Group info for User [' . $user->getPeoplesoftId() . '] to [' . $newUser->getPeoplesoftId() . ']');
                    $courseGroup->addUser($newUser);
                    $courseGroup->removeUser($user);
                    $em->persist($courseGroup);
                }
            }


            //check if we need to replace the entry in Welcome Display
            $programmeSubscriptions = $em
                ->getRepository(CourseSubscription::class)
                ->findBy(['user' => $user->getId(),'programme' => $programme->getId()]);

            if( count($programmeSubscriptions) === 1 ) {
                /** @var ProgrammeUser $programmeUser */
                $programmeUser = $em
                    ->getRepository(ProgrammeUser::class)
                    ->findOneBy(['user' => $user->getId(),'programme' => $programme->getId()]);

                if( $programmeUser ) {
                    $this->log("Removing user [" . $user->getId() . "] as core group from the programme [" . $programme->getId() . "]");
                    $programmeUser->setUser($newUser);

                    $em->persist($programmeUser);
                }
            }

            //replace user subscription
            $this->log('Replacing Course subscription for User [' . $user->getPeoplesoftId() . '] to [' . $newUser->getPeoplesoftId() . ']');
            $subscription->setUser($newUser);
            $em->persist($subscription);
        }

        $em->flush();

        // push notification
        $notify = $this->notify;
        $notify->setLogUuid($request);
        $notify->message($course, self::$ENTITY_NAME);

        return $course->getAllUsers();
    }

    /**
     * Handler function to Un-assign a user from a course
     *
     * @param $courseId
     * @param $peoplesoftId
     *
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function unAssignUserFromCourse(Request $request, $courseId, $peoplesoftId)
    {

        $this->checkReadWriteAccess($request,$courseId);

        $this->log('UNASSIGNING USER FROM COURSE: ' . $courseId);

        $em     = $this->entityManager;

        /** @var Course $course */
        $course = $this->findById(self::$ENTITY_NAME, $courseId);

        /** @var User $user */
        $user   = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peoplesoftId]);

        $programme = $course->getProgramme();

        if ($user) {
            /** @var CourseSubscription $subscription */
            $subscription = $this->entityManager
                ->getRepository(CourseSubscription::class)
                ->findOneBy(['course' => $course, 'user' => $user]);

            if ($subscription) {
                //remove user as host from any session in the course
                $this->log("Removing user as hosts from any session in the course");
                $sessions = $course->getSessions();

                /** @var Session $session */
                foreach( $sessions as $session ) {
                    $session->removeProfessor( $user );
                    $em->persist($session);
                }

                //remove user as core group if the user does not belong to any other programme
                $this->log("Checking if user needs to be removed from core group of the programme");
                $programmeSubscriptions = $em
                    ->getRepository(CourseSubscription::class)
                    ->findBy(['user' => $user->getId(),'programme' => $programme->getId()]);

                //user only belongs to this course for the whole programme, delete the user from programme core group
                if( count($programmeSubscriptions) === 1 ) {
                    $this->log("Removing user [" . $user->getId() . "] as core group from the programme [" . $programme->getId() . "]");

                    /** @var ProgrammeUser $programmeUser */
                    $programmeUser = $em
                        ->getRepository(ProgrammeUser::class)
                        ->findOneBy(['user' => $user->getId(),'programme' => $programme->getId()]);

                    if( $programmeUser ) {
                        $em->remove($programmeUser);
                        $em->flush();
                    }

                    //*********************************************************************************************
                    // Because this is the last course that the user belongs to, delete him also to vanilla group

                    $vanillaProgrammeGroup = $em
                        ->getRepository(VanillaProgrammeGroup::class)
                        ->findBy(['programme' => $programme]);

                    if (count($vanillaProgrammeGroup) > 0) {

                        /** @var VanillaProgrammeGroup $vanillaGroup */
                        foreach ($vanillaProgrammeGroup as $vanillaGroup) {

                            /** @var VanillaUserGroup $vanillaUserGroup */
                            $vanillaUserGroup = $em->getRepository(VanillaUserGroup::class)
                                ->findOneBy(['group' => $vanillaGroup, 'user' => $user]);

                            if ($vanillaUserGroup){
                                $this->log('Removing from Group: '.$vanillaGroup->getVanillaGroupName().' User ['.$user->getId().']');
                                $vanillaUserGroup->setRemove(true);
                            }
                        }
                        $em->flush();
                    }
                }

                $this->log("Removing user [" . $user->getId() . "] subscription from course [" . $course->getId() . "]");
                $course = $course->removeUser($subscription);

                $em->remove($subscription);
                $em->flush();

                // REMOVE USER FROM DEFAULT COURSE GROUP
                $courseDefaultGroupA = $this->entityManager
                    ->getRepository(Group::class)
                    ->findBy(['course_default' => TRUE, 'course' => $course->getId()]);

                if(count($courseDefaultGroupA) > 0) {
                    // This Course has a default Group
                    $courseDefaultGroup = $courseDefaultGroupA[0];
                    $courseDefaultGroup = $courseDefaultGroup->removeUser($user);
                    $em = $this->entityManager;
                    $em->persist($courseDefaultGroup);
                    $em->flush();
                }

                // push notifications
                $notify = $this->notify;
                $notify->setLogUuid($request);
                $notify->message($course, self::$ENTITY_NAME);

            } else {
                throw new InvalidResourceException(['people' => ['Invalid Peoplesoft Ids entered. User not assigned to this course.']]);
            }
        } else {
            throw new InvalidResourceException(['people' => ['Invalid Peoplesoft Ids entered. User not assigned to this course.']]);
        }
    }

    /**
     * @param $userIds
     * @param $role
     * @return array
     */
    protected function getEnrichedProfiles(Request $request, $userIds, $role)
    {

        $profiles = [];
        foreach ($userIds[ $role ] as $userId) {
            $user = $this->getUserProfileData($request, $userId);
            if ($user) {
                $profiles[] = $user;
            }
        }

        return $profiles;
    }
}
