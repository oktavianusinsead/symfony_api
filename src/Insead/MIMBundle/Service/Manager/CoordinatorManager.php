<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Insead\MIMBundle\Entity\Administrator;

use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Exception\ConflictFoundException;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\PermissionDeniedException;
use Insead\MIMBundle\Exception\SessionTimeoutException;
use Insead\MIMBundle\Service\AIPService;
use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\StudyNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use Insead\MIMBundle\Service\BoxManager\BoxManager;
use Insead\MIMBundle\Service\StudyCourseBackup as Backup;

use Insead\MIMBundle\Entity\UserToken;
use Symfony\Component\HttpFoundation\Request;


class CoordinatorManager extends Base
{
    protected $superAdminList;

    protected $AIPService;
    protected $userProfileManager;

    public function loadServiceManager($config, AIPService $AIPService, UserProfileManager $userProfileManager )
    {
        $this->superAdminList     = $config["study_super"];
        $this->AIPService         = $AIPService;
        $this->userProfileManager = $userProfileManager;
    }

    /**
     * Function to get list of coordinators/administrators
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function getCoordinators(Request $request)
    {

        $show = $request->query->get("show") ? :null;

        $superAdmins = [];
        if( $this->superAdminList ) {
            $superAdmins = explode(",",(string) $this->superAdminList);
        }

        $this->log("Get all Coordinators");

        $coordinators = [];

        $result = $this->entityManager
            ->getRepository(Administrator::class)
            ->findAll();

        $this->log( "Coordinators found: " . count($result) );

        /* @var $itemObj Administrator */
        foreach( $result as $itemObj ) {

            $super = false;
            if( array_search($itemObj->getPeoplesoftId(),$superAdmins) !== false ) {
                $super = true;
            }

            $coordinator = ["id"                => $itemObj->getPeoplesoftId(), "peoplesoft_id"     => $itemObj->getPeoplesoftId(), "profile_id"        => $itemObj->getPeoplesoftId(), "created_at"        => $itemObj->getCreated(), "last_login"        => $itemObj->getLastLogin(), "is_blocked"        => $itemObj->getBlocked(), "is_faculty"        => $itemObj->getFaculty(), "is_support_only"   => $itemObj->getSupportOnly(), "is_super"          => $super];

            if(
                is_null($show)
                || ( !is_null($show) && $show == "all" )
                || ( !is_null($show) && $show == "super" && $super)
                || ( !is_null($show) && $show == "faculty" && $itemObj->getFaculty())
                || ( !is_null($show) && $show == "allowed" && !$super && !$itemObj->getFaculty() && !$itemObj->getBlocked() && !$itemObj->getSupportOnly() )
                || ( !is_null($show) && $show == "support" && !$super && !$itemObj->getFaculty() && !$itemObj->getBlocked() && $itemObj->getSupportOnly() )
                || ( !is_null($show) && $show == "blocked" && !$super && !$itemObj->getFaculty() && $itemObj->getBlocked() )
            ) {
                array_push($coordinators, $coordinator);
            }
        }

        if(
            is_null($show)
            || ( !is_null($show) && $show == "all" )
            || ( !is_null($show) && $show == "super" )
        ) {
            //include floating super admins, part of the list but not yet in database
            foreach( $superAdmins as $adminId ) {
                $found = false;

                foreach( $coordinators as $item ) {
                    if( $adminId == $item["peoplesoft_id"] ) {
                        $found = true;
                        break;
                    }
                }

                if( !$found ) {
                    $coordinator = ["id"                => $adminId, "peoplesoft_id"     => $adminId, "profile_id"        => $adminId, "created_at"        => "", "last_login"        => "", "is_blocked"        => false, "is_super"          => true];

                    array_push( $coordinators, $coordinator );
                }
            }
        }

        return ['coordinators' => $coordinators];
    }

    /**
     * Function to get list of coordinators/administrators
     *
     * @param Request $request Request Object
     *
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addCoordinators(Request $request)
    {

        $adminIds = $request->get("admins");

        $this->log("Add Coordinators: " . implode(",",$adminIds) );

        $em = $this->entityManager;

        $validCount = 0;

        $processedItems = [];

        foreach( $adminIds as $adminId ) {

            $found = false;
            $valid = false;
            $isFaculty = false;
            $user      = false;

            $existingAdmin = $this->entityManager
                ->getRepository(Administrator::class)
                ->findBy([
                    "peoplesoft_id" => $adminId
                ]);

            $this->log("Checking if " . $adminId . " exists in DB: " . count($existingAdmin));

            if( count($existingAdmin) == 0 ) {
                //check if we have tokens for this admin
                $user = $em->getRepository(User::class)
                    ->findOneBy([
                        "peoplesoft_id" => $adminId
                    ]);

                if (!$user){
                    $user = new User();
                    $user->setPeoplesoftId($adminId);
                    $em->persist($user);
                }

                if (!$user->getCacheProfile()){
                    $body = $this->AIPService->getUserApi($user->getPeoplesoftId());
                    $this->userProfileManager->saveESBWithPayload($body);
                    $this->entityManager->refresh($user);
                }

                if ($user->getCacheProfile()) {
                    $found = true;
                }
            }

            //if found, validate that the constituent type is valid for admin
            if( $found ) {
                $userConstituentTypes = array_map('intval',array_map('trim', explode(",",(string) $user->getCacheProfile()->getConstituentTypes())));

                foreach($userConstituentTypes as $userConstituentType) {
                    if(in_array($userConstituentType, self::$MIM_ADMIN_ROLES_ID)) {
                        $this->log("STAFF/FACULTY ROlE FOUND for user!!");
                        $valid = true;
                    }

                    if( $userConstituentType == 7 ) { // Faculty
                        $isFaculty = true;
                        break;
                    }
                }

                //create the administrator record
                if( $valid ) {
                    $admin = $this->entityManager
                        ->getRepository(Administrator::class)
                        ->findOneBy([
                            "peoplesoft_id" => $adminId
                        ]);

                    if( $admin) {
                        if( $admin->getFaculty() != $isFaculty ) {
                            $admin->setFaculty($isFaculty);

                            $em->persist($admin);
                        }
                    } else {
                        $admin = new Administrator();
                        $admin->setPeoplesoftId($adminId);
                        $admin->setBlocked(true);
                        $admin->setFaculty($isFaculty);

                        $em->persist($admin);

                        array_push($processedItems,$admin);

                        $validCount++;
                    }
                }
            }

            //ignore other items if it has exceeded 5 items to process
            if( $validCount >= 5 ) {
                break;
            }
        }

        if( count($processedItems) ) {
            $em->flush();
        }

        return ['coordinators' => $processedItems];
    }

    /**
     * Function to retrieve an administrator
     *
     * @param Request       $request            Request Object
     * @param int           $peoplesoftId       peoplesoftid of the administrator
     *
     * @return array
     */
    public function getCoordinator(Request $request, $peoplesoftId)
    {

        $superAdmins = [];
        if( $this->superAdminList ) {
            $superAdmins = explode(",",(string) $this->superAdminList);
        }

        $this->log( "Retrieving Admin: " . $peoplesoftId );

        $admin = $this->entityManager
            ->getRepository(Administrator::class)
            ->findOneBy([
                "peoplesoft_id" => $peoplesoftId
            ]);

        $super = false;
        if( array_search($admin->getPeoplesoftId(),$superAdmins) !== false ) {
            $super = true;
        }

        $coordinator = ["id"                => $admin->getPeoplesoftId(), "peoplesoft_id"     => $admin->getPeoplesoftId(), "profile_id"        => $admin->getPeoplesoftId(), "created_at"        => $admin->getCreated(), "last_login"        => $admin->getLastLogin(), "is_blocked"        => $admin->getBlocked(), "is_faculty"        => $admin->getFaculty(), "is_support_only"   => $admin->getSupportOnly(), "is_super"          => $super];

        return ['coordinator' => $coordinator];
    }

    /**
     * Function to update an administrator
     *
     * @param Request $request Request Object
     * @param int $peoplesoftId peoplesoftid of the administrator
     *
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateCoordinator(Request $request, $peoplesoftId)
    {

        $blocked = $request->get('is_blocked');
        $supportOnly = $request->get('is_support_only');

        $superAdmins = [];
        if( $this->superAdminList ) {
            $superAdmins = explode(",",(string) $this->superAdminList);
        }

        if( $blocked ) {
            $this->log( "Updating Admin: " . $peoplesoftId . " - Request to block " );
        } else {
            $blocked = false;
            if( $supportOnly ) {
                $this->log( "Updating Support: " . $peoplesoftId . " - Request to allow " );
            } else {
                $supportOnly = false;
                $this->log( "Updating Admin: " . $peoplesoftId . " - Request to allow " );
            }
        }

        $admin = $this->entityManager
            ->getRepository(Administrator::class)
            ->findOneBy([
                "peoplesoft_id" => $peoplesoftId
            ]);

        if( $admin ) {
            $this->log( "Coordinator found " );

            $super = false;
            if( array_search($admin->getPeoplesoftId(),$superAdmins) !== false ) {
                $super = true;
            }

            if( $super ) {
                $this->log("Super Administrator cannot be updated");
            } else {
                $admin->setBlocked($blocked);
                $admin->setSupportOnly($supportOnly);

                $em = $this->entityManager;
                $em->persist($admin);

                if( $admin->getBlocked() ) {
                    //check if we have tokens for this admin
                    $user = $em->getRepository(User::class)
                        ->findOneBy([
                            "peoplesoft_id" => $admin->getPeoplesoftId()
                        ]);

                    if( $user ) {
                        $now = new \DateTime();

                        $userTokens = $this->entityManager
                            ->getRepository(UserToken::class)
                            ->findBy([
                                "user" => $user
                            ]);

                        $this->log("Found User Tokens: " . count($userTokens));

                        $adminArr = [];
                        $studentArr = [];

                        //expire all admin tokens
                        /** @var UserToken $token */
                        foreach( $userTokens as $token ) {
                            if( $token->getScope() == "studysuper" || $token->getScope() == "studyadmin" ) {

                                $token->setTokenExpiry($now);

                                $em->persist($token);

                                $adminArr[] = [
                                    "scope" => $token->getScope(),
                                    "id" => $token->getId()
                                ];
                            } else {
                                $studentArr[] = [
                                    "scope" => $token->getScope(),
                                    "id" => $token->getId()
                                ];
                            }
                        }

                        $this->log("Admin Tokens disabled: " . json_encode($adminArr));
                        $this->log("Student Tokens kept: " . json_encode($studentArr));
                    }
                }

                $em->flush();
            }
        }

        $coordinator = ["id"                => $admin->getPeoplesoftId(), "peoplesoft_id"     => $admin->getPeoplesoftId(), "profile_id"        => $admin->getPeoplesoftId(), "created_at"        => $admin->getCreated(), "last_login"        => $admin->getLastLogin(), "is_blocked"        => $admin->getBlocked(), "is_faculty"        => $admin->getFaculty(), "is_support_only"   => $admin->getSupportOnly(), "is_super"          => $super];

        return ['coordinator' => $coordinator];
    }

    /**
     * Function to add user to admin list
     *
     * @param Request $request Request Object
     *
     * @return array
     * @throws ConflictFoundException
     * @throws InvalidResourceException
     */
    public function addUserToAdmin(Request $request)
    {
        $peoplesoft_id = $request->get('peoplesoft_id');
        $blocked = $request->get('is_blocked');
        $supportOnly = $request->get('is_support');
        $isFaculty = $request->get('is_faculty');

        if (!isset($peoplesoft_id) || !isset($supportOnly) || !isset($blocked) || !isset($isFaculty)) {
            throw new ConflictFoundException('Missing fields');
        }

        $em = $this->entityManager;
        /** @var User $user */
        $user = $em->getRepository(User::class)
            ->findOneBy([
                "peoplesoft_id" => $peoplesoft_id
            ]);

        if (!$user) {
            throw  new InvalidResourceException(['error' => 'PeoplesoftID not found']);
        }

        /** @var Administrator $admin */
        $admin = $em->getRepository(Administrator::class)
            ->findOneBy([
                "peoplesoft_id" => $peoplesoft_id
            ]);

        $message = "Updated";
        if (!$admin) {
            $admin = new Administrator();
            $admin->setPeoplesoftId($user->getPeoplesoftId());

            $message = "Added";
        }

        $admin->setBlocked($blocked);
        $admin->setSupportOnly($supportOnly);
        $admin->setFaculty($isFaculty);

        try {
            $em->persist($admin);
            $em->flush();
        } catch (\Exception $e) {
            throw new InvalidResourceException('Unable to add/update Admin');
            $this->log($e->getMessage());
        }

        return ['success' => $message];
    }
}
