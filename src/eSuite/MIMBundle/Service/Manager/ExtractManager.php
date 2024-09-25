<?php

namespace esuite\MIMBundle\Service\Manager;

use esuite\MIMBundle\Entity\Administrator;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Organization;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\User;

use esuite\MIMBundle\Entity\UserProfileCache;
use esuite\MIMBundle\Exception\PermissionDeniedException;
use Symfony\Component\HttpFoundation\Request;
use DateTime;


class ExtractManager extends Base
{
    protected $rootDir;

    public function loadServiceManager($config)
    {
        $this->rootDir = $config["kernel_root"];
    }

    /**
     * Function to extractAllProgramme
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function extractAllProgramme(Request $request)
    {

        $this->log("Get all Programme");

        $programmes = [];

        $result = $this->entityManager
            ->getRepository(Programme::class)
            ->findAll();

        $this->log( "Programme found: " . count($result) );

        foreach( $result as $itemObj ) {

            /* @var $itemObj Programme */
            $programme = ["id"                => $itemObj->getId(), "name"              => $itemObj->getName(), "published"         => $itemObj->getPublished(), "code"              => $itemObj->getCode(), "created_at"        => $itemObj->getCreated(), "updated_at"        => $itemObj->getUpdated()];

            array_push( $programmes, $programme );
        }

        return ['programmes' => $programmes];
    }

    /**
     * Function to extractAllCourse
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function extractAllCourse(Request $request)
    {

        $this->log("Get all Course");

        $courses = [];

        $result = $this->entityManager
            ->getRepository(Course::class)
            ->findAll();

        $this->log( "Course found: " . count($result) );

        foreach( $result as $courseObj ) {

            /* @var $courseObj Course */
            $course = ["id"                => $courseObj->getId(), "programme_id"      => $courseObj->getProgrammeId(), "name"              => $courseObj->getName(), "published"         => $courseObj->getPublished(), "box_group_id"      => $courseObj->getBoxGroupId(), "country"           => $courseObj->getCountry(), "timezone"          => $courseObj->getTimezone(), "start_date"        => $courseObj->getStartDate(), "end_date"          => $courseObj->getEndDate(), "created_at"        => $courseObj->getCreated(), "updated_at"        => $courseObj->getUpdated()];

            array_push( $courses, $course );
        }

        return ['courses' => $courses];
    }

    /**
     * Function to extractAllSessions
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function extractAllSession(Request $request)
    {

        $this->log("Get all Sessions");

        $sessions = [];

        $result = $this->entityManager
            ->getRepository(Session::class)
            ->findAll();

        $this->log( "Sessions found: " . count($result) );

        foreach( $result as $sessionObj ) {

            /* @var $sessionObj Session */

            $session = ["id"                => $sessionObj->getId(), "name"              => $sessionObj->getName(), "course_id"         => $sessionObj->getCourseId(), "published"         => $sessionObj->getPublished(), "box_folder_id"     => $sessionObj->getBoxFolderId(), "box_folder_name"   => $sessionObj->getBoxFolderName(), "created_at"        => $sessionObj->getCreated(), "updated_at"        => $sessionObj->getUpdated()];

            array_push( $sessions, $session );
        }

        return ['sessions' => $sessions];
    }

    /**
     * Function to extractAllUser
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function extractAllUser(Request $request)
    {

        $this->log("Get all User");

        $users = [];

        $result = $this->entityManager
            ->getRepository(User::class)
            ->findAll();

        $this->log( "User found: " . count($result) );

        foreach( $result as $itemObj ) {

            /* @var $itemObj User */
            $user = ["id"                => $itemObj->getId(), "box_id"            => $itemObj->getBoxId(), "box_email"         => $itemObj->getBoxEmail(), "peoplesoft_id"     => $itemObj->getPeoplesoftId(), "agreement"         => $itemObj->getAgreement(), "created_at"        => $itemObj->getCreated(), "updated_at"        => $itemObj->getUpdated()];

            array_push( $users, $user );
        }

        return ['users' => $users];
    }

    /**
     * Function to extractAllUser
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function extractAllAdmin(Request $request)
    {

        $this->log("Get all Administrator");

        $admins = [];

        $result = $this->entityManager
            ->getRepository(Administrator::class)
            ->findAll();

        $this->log( "Admin found: " . count($result) );

        foreach( $result as $itemObj ) {

            /* @var $itemObj Administrator */
            $admin = ["id"                => $itemObj->getId(), "peoplesoft_id"     => $itemObj->getPeoplesoftId(), "created_at"        => $itemObj->getCreated(), "updated_at"        => $itemObj->getUpdated(), "last_login"        => $itemObj->getLastLogin()];

            array_push( $admins, $admin );
        }

        return ['administrators' => $admins];
    }

    /**
     * Function to extractAllNonParticipant
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function extractAllNonParticipant(Request $request)
    {

        $this->log("Get all Non Participants");

        $nonParticipants = [];

        $result = $this->entityManager
            ->getRepository(CourseSubscription::class)
            ->findAll();

        $this->log( "Subscriptions found: " . count($result) );

        foreach( $result as $itemObj ) {

            /* @var $itemObj CourseSubscription */
            $nonParticipant = ["peoplesoft_id"     => $itemObj->getUser()->getPeoplesoftId(), "role"              => $itemObj->getRole()->getName(), "course_id"         => $itemObj->getCourse()->getId()];

            array_push( $nonParticipants, $nonParticipant );
        }

        return ['non-participants' => $nonParticipants];
    }


    /**
     * Function to extractComposerLock
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function extractComposerLock(Request $request)
    {

        $this->log("Get composer lock information");

        $composerInfo = "";

        if( file_exists($this->rootDir . "/../composer.lock") ) {
            $composerInfo = file_get_contents( $this->rootDir . "/../composer.lock" );
        }

        return ['composer' => base64_encode($composerInfo)];
    }

    /**
     * Function to extractProfileCache
     *
     * @param Request       $request            Request Object
     * @param String        $peoplesoftId
     *
     * @return array
     */
    public function extractProfileCache(Request $request, $peoplesoftId)
    {

        $this->log("Get profile cache of " . $peoplesoftId);

        $response = [];

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy( ['peoplesoft_id' => $peoplesoftId] );

        $this->log( "User found: " . count($user) );

        if( $user ) {
            $userProfileCache = $this->entityManager
                ->getRepository(UserProfileCache::class)
                ->findOneBy( ['user' => $user] );

            $response = $userProfileCache;
        }

        return ['profiles' => $response];
    }

    /**
     * Function to extractOrganization
     *
     * @param Request       $request            Request Object
     * @param String        $extOrgId
     *
     * @return array
     */
    public function extractOrganization(Request $request, $extOrgId)
    {

        $this->log("Get organization title of " . $extOrgId);

        $response = [];

        /** @var User $Organization */
        $org = $this->entityManager
            ->getRepository(Organization::class)
            ->findOneBy( ['ext_org_id' => $extOrgId] );

        $this->log( "Organization found: " . count($org) );

        if( $org ) {
            $response = $org;
        }

        return ['organization' => $response];
    }

    /**
     * Function to extract from a any entity
     *
     * @param Request $request Request Object
     * @return array
     *
     * @throws PermissionDeniedException
     */
    public function extractCustomAll(Request $request)
    {

        if ($request->headers->get("keyColumn") && $request->headers->get("keyId")){
            $keyColumn = $request->headers->get("keyColumn");
            $keyId     = $request->headers->get("keyId");
            if (strlen($keyId) > 0 && strlen($keyColumn) > 0 ){
                $keyColumn = json_decode($keyColumn, true);
                if (count($keyColumn) > 0){
                    try{
                        $builder = $this->entityManager->createQueryBuilder();

                        $result = $builder->select('ce')->from('esuite\MIMBundle\Entity\\'.$keyId,'ce');

                        foreach ($keyColumn as $keyItem => $keyValue){
                            if (DateTime::createFromFormat('Y-m-d', $keyValue) !== FALSE) {
                                $timeValue1 = new \DateTime($keyValue);
                                $timeValue1->format('Y-m-d');

                                $interval = new \DateInterval('P1D');
                                $timeValue2 = new \DateTime($keyValue);
                                $timeValue2->format('Y-m-d');
                                $timeValue2->add($interval);

                                $result->andWhere($this->entityManager->createQueryBuilder()->expr()->between(
                                    'ce.'.$keyItem,
                                    ':from',
                                    ':to'
                                ));
                                $result->setParameter('from', $timeValue1);
                                $result->setParameter('to', $timeValue2);
                            } else {
                                $result->andWhere('ce.'.$keyItem.' = :keyValue');
                                $result->setParameter('keyValue', $keyValue);
                            }
                        }

                        return $result->getQuery()->getArrayResult();
                    } catch (\Exception $e) {
                        throw new PermissionDeniedException($e->getMessage());
                    }
                } else {
                    throw new PermissionDeniedException('You are not authorized to access this API Endpoint.');
                }
            } else {
                throw new PermissionDeniedException('You are not authorized to access this API Endpoint.');
            }
        } else {
            throw new PermissionDeniedException('You are not authorized to access this API Endpoint.');
        }
    }

}
