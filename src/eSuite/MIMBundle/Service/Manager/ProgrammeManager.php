<?php

namespace esuite\MIMBundle\Service\Manager;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use esuite\MIMBundle\Entity\Group;
use esuite\MIMBundle\Entity\GroupActivity;
use esuite\MIMBundle\Entity\Role;
use esuite\MIMBundle\Entity\Subtask;
use esuite\MIMBundle\Entity\Task;
use esuite\MIMBundle\Entity\UserProfileCache;
use esuite\MIMBundle\Exception\PermissionDeniedException;
use esuite\MIMBundle\Service\S3ObjectManager;
use Doctrine\ORM\Query;
use esuite\MIMBundle\Entity\Activity;
use esuite\MIMBundle\Entity\Administrator;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\GroupSession;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\ProgrammeAdministrator;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\UserToken;
use Exception;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Exception\ForbiddenException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\Criteria;


class ProgrammeManager extends Base
{
    /**
     *  @var string
     *  Name of the Entity
     */
    public static string $ENTITY_NAME = "Programme";

    /**
     * @var SesClient AWS SES Client variable
     */
    private SesClient $sesClient;

    protected $superAdminList;
    protected S3ObjectManager $s3;
    protected LoginManager $login;
    protected string $symfony_environment;
    protected string $aws_ses_from_email;
    protected string $copyProgrammeTransactionId;
    protected array $copyResult;
    protected array $copyProgrammeGroupMapping;

    public function loadServiceManager(S3ObjectManager $s3, LoginManager $login, $config )
    {
        $this->superAdminList            = $config["edot_super"];
        $this->s3                        = $s3;
        $this->login                     = $login;
        $this->copyResult                = [];
        $this->copyProgrammeGroupMapping = [];
        $this->symfony_environment       = strtolower((string) $config["symfony_environment"]);

        // Load credentials from Container properties
        $AWS_CREDENTIALS = ['key' => $config['aws_access_key_id'], 'secret' => $config['aws_secret_key']];
        $AWS_REGION = $config['aws_region'];

        $sesConfigArray = ['version' => 'latest', 'region' => $AWS_REGION];

        if( isset($config["symfony_environment"]) && $config["symfony_environment"] === 'dev' ) {
            $sesConfigArray['credentials'] = $AWS_CREDENTIALS;
        }

        $this->sesClient = new SesClient($sesConfigArray);
        $this->aws_ses_from_email = $config['aws_ses_from_email'];

        $this->logger->info("Created SES Client successfully. With credentials");
    }


    /**
     * Function to create an Programme
     *
     * @param Request $request Request Object
     *
     * @return Response
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function createProgramme(Request $request)
    {
        $paramList = "name,code,welcome,link_webmail,link_yammer,link_myesuite,link_learninghub,link_faculty_blog,link_knowledge,link_amphihq,published,private,starts_on_sunday,company_logo,company_logo_size,with_discussions,view_type,learning_journey";
        $data = $this->loadDataFromRequest( $request, $paramList );

        $this->log("PROGRAMME NAME: ".$request->get('name'));

        $programme = new Programme();
        $programme = $this->processProgramme( $programme, $data );

        $responseObj = $this->createRecord(self::$ENTITY_NAME, $programme);


        //create programme admin record and mark as owner
        $user = $this->getCurrentUserObj($request);

        $programmeAdmin = new ProgrammeAdministrator();
        $programmeAdmin->setProgramme($programme);
        $programmeAdmin->setUser($user);
        $programmeAdmin->setOwner(true);

        $em = $this->entityManager;
        $em->persist($programmeAdmin);
        $em->flush();


        return $responseObj;
    }

    /**
     * Function to retrieve an existing Programme
     *
     * @param Request       $request            Request Object
     * @param String        $programmeId        id of the Programme
     *
     * @throws ResourceNotFoundException
     * @throws ForbiddenException
     *
     * @return array
     */
    public function getProgramme(Request $request, $programmeId)
    {

        $scope = $this->getCurrentUserScope($request);
        $user = $this->getCurrentUserObj($request);

        $programme = [];

        if (
            $scope == "mimstudent"
            || $scope == "edotstudent"
        ) {
            $this->log("getting Programme " . $programmeId . " for Student");

            if(sizeof($this->getPublishedSubscribedCourses($request,$programmeId)) === 0) {
                throw new ForbiddenException();
            }

            /** @var Programme $programme */
            $programme = $this->entityManager
                ->getRepository(Programme::class)
                ->findOneBy(['id' => $programmeId]);

            if(!$programme) {
                $this->log('Programme not found');
                throw new ResourceNotFoundException('Programme not found');
            }

            $programme->setForParticipant(true);
            $programme->setIncludeHidden(true);
            $programme->setRequestorId($user->getId());

            // For mimstudent scope, only subscribed courses needs to be returned/serialised
            $subscribedCourses = [];
            foreach($this->getPublishedSubscribedCourses($request,$programmeId) as $subscribedCourse) {
                /** @var Course $subscribedCourse */
                array_push($subscribedCourses, $subscribedCourse->getId());
            }
            // Tell the model to show only subscribed courses passed as argument here
            $programme->showOnlySubscribedCourses($subscribedCourses);

        } else if (
            $scope == "edotadmin"
            || $scope == "edotsuper"
            || $scope == "edotssvc"
            || $scope == "edotsvc"
        ) {
            $this->log("getting Programme " . $programmeId . " for Admin");

            $programme = $this->entityManager
                ->getRepository(Programme::class)
                ->findOneBy(['id' => $programmeId]);

            if(!$programme) {
                $this->log('Programme not found');
                throw new ResourceNotFoundException('Programme not found');
            }

            //check if user has access to the programme
            $programme->setRequestorId($user->getId());

            if( $scope == "edotsuper" ) {
                //check if user has access to the programme
                $programme->setRequestorScope($scope);

            } else  {
                $programme->setIncludeHidden(true);
                if(!$programme->checkIfMy() && $programme->getPrivate()) {
                    $this->log('Programme not found');
                    throw new ResourceNotFoundException('Programme not found');
                }
            }

        }

        return [strtolower(self::$ENTITY_NAME) => $programme];
    }

    /**
     * Function to retrieve existing Programmes
     *
     * @param Request $request Request Object
     *
     * @return array
     * @throws Exception
     *
     */
    public function getProgrammes(Request $request)
    {

        $scope = $this->getCurrentUserScope($request);
        $userId = $this->getCurrentUserId($request);
        $em = $this->entityManager;

        $programmes = [];

        if (
            $scope == "mimstudent"
            || $scope == "edotstudent"
        ) {
            $this->log("getting Programmes for Student");

            //Get Courses the current user is assigned to as 'student' and are published
            /** @var Query $query */
            $query = $em->createQuery(
                'SELECT p FROM esuite\MIMBundle\Entity\Programme p
                                JOIN p.courses c
                                JOIN c.courseSubscriptions cs
                                JOIN cs.user u
                                WHERE c.published = :published and p.published = :published and u.id = :user_id'
            )
                ->setParameter('published', TRUE)
                ->setParameter('user_id', $userId);

            $programmesList = $query->getResult();

            // Serialize only published sub-entities
            foreach($programmesList as $programme) {
                /** @var Programme $programme */
                $programme->serializeOnlyPublished(TRUE);
                $programme->setHideCourses(TRUE);
                $programme->setForParticipant(true);
                $programme->setIncludeHidden(true);
                $programme->setRequestorId($userId);

                if (!$programme->getArchived()) {
                    array_push($programmes, $programme);
                } else {
                    if ($programme->getArchivedRemainingDays() > 0) {
                        array_push($programmes, $programme);
                    }
                }
            }
        } elseif(
            $scope == "edotadmin"
            || $scope == "edotsuper"
        ) {
            $this->log("getting Programmes for Admin");

            $type = $request->query->get("type");
            $search = $request->query->get("search");
            $month = $request->query->get("month");
            $year = $request->query->get("year");

            $matchCourseDate = false;

            $this->log("Getting - Type:" . $type . ", Search:" . $search);

            $items = $em
                ->getRepository(Programme::class)
                ->findAll();

            /** @var Programme $programme */
            foreach( $items as $programme ) {
                $programme->setRequestorId($userId);
                $programme->setIncludeHidden(true);

                $programme->setRequestorScope($scope);

                $toInclude = true;
                //if type is given, filter result
                if( $type ) {
                    $toInclude = false;

                    if( $type == "live" ) {
                        $toInclude = $programme->checkIfLive();
                    } else if( $type == "pending" ) {
                        $toInclude = $programme->checkIfPending();
                    } else if( $type == "completed" ) {
                        $toInclude = $programme->checkIfCompleted();
                    } else if( $type == "my" ) {
                        $toInclude = $programme->checkIfMy();
                    } else if( $type == "mylive" ) {
                        $toInclude = $programme->checkIfMy() && $programme->checkIfLive();
                    } else if( $type == "mypending" ) {
                        $toInclude = $programme->checkIfMy() && $programme->checkIfPending();
                    } else if( $type == "mycompleted" ) {
                        $toInclude = $programme->checkIfMy() && $programme->checkIfCompleted();
                    }
                }

                //for normal admin, only show their programme and other published non-private programmes
                if( $scope != "edotsuper" ) {
                    $toInclude = $programme->checkIfMy() || (!$programme->checkIfMy() && !$programme->getPrivate() && $programme->getPublished());
                }

                //filter items that are supposed to be displayed, based on keyword
                if( $toInclude && !is_null($search) && $search != "" ) {
                    $toInclude = false;

                    $toInclude = $toInclude || (str_contains( strtolower($programme->getName()), strtolower($search) ));
                    $toInclude = $toInclude || (str_contains( strtolower($programme->getWelcome()), strtolower($search) ));

                    if( !$toInclude ) {
                        /** @var Course $course*/
                        foreach( $programme->getPublishedCourses() as $course ) {
                            $toInclude = $toInclude || (str_contains( strtolower($course->getName()), strtolower($search) ));
                            $toInclude = $toInclude || (str_contains( strtolower($course->getAbbreviation()), strtolower($search) ));

                            if( $toInclude ) {
                                break;
                            }
                        }
                    }
                }

                //filter items that are supposed to be displayed, based on year+month
                if( $toInclude && is_numeric($year) ) {
                    $toInclude = false;

                    //with month
                    if( is_numeric($month) && $month > 0 && $month <= 12 ) {
                        $filterDate = new \DateTime( $year . '-' . $month . '-01');

                        //based on programme dates
                        if( !is_null($programme->getStartDate()) && !is_null($programme->getEndDate()) ) {

                            $progStart = $programme->getStartDate();
                            $progStart->modify('first day of this month');
                            $progStart->setTime(0,0,0);

                            $progEnd = $programme->getEndDate();
                            $progEnd->modify('last day of this month');
                            $progEnd->setTime(23,59,59);

                            $toInclude = $toInclude || ( $filterDate >= $progStart && $filterDate <= $progEnd );
                        }

                        //based on course dates only
                        if( $matchCourseDate && $toInclude ) {
                            $toInclude = false;
                            /** @var Course $course*/
                            foreach( $programme->getPublishedCourses() as $course ) {
                                $courseStart = $course->getStartDate();
                                $courseStart->modify('first day of this month');
                                $courseStart->setTime(0,0,0);

                                $courseEnd = $course->getEndDate();
                                $courseEnd->modify('last day of this month');
                                $courseEnd->setTime(23,59,59);

                                $toInclude = $toInclude || ( $filterDate >= $courseStart && $filterDate <= $courseEnd );

                                if( $toInclude ) {
                                    break;
                                }
                            }
                        }

                    //year only
                    } else {
                        //based on programme dates
                        if( !is_null($programme->getStartDate()) && !is_null($programme->getEndDate()) ) {

                            $progStartY = $programme->getStartDate()->format("Y");

                            $progEndY = $programme->getEndDate()->format("Y");

                            $toInclude = $toInclude || ( $year >= $progStartY && $year <= $progEndY );
                        }

                        //based on course dates only
                        if( $matchCourseDate && $toInclude ) {
                            $toInclude = false;
                            /** @var Course $course*/
                            foreach( $programme->getPublishedCourses() as $course ) {
                                $courseStartY = $course->getStartDate()->format("Y");

                                $courseEndY = $course->getEndDate()->format("Y");

                                $toInclude = $toInclude || ( $year >= $courseStartY && $year <= $courseEndY );

                                if( $toInclude ) {
                                    break;
                                }
                            }
                        }
                    }
                }

                if (!$programme->getArchived()) {
                    if ($toInclude) {
                        array_push($programmes, $programme);
                    }
                }
            }
        }

        $responseObj = ['programmes' => $programmes];

        return $responseObj;
    }

    /**
     * Function to update an existing Programme
     *
     * @param Request $request Request Object
     * @param String $programmeId id of the Programme
     *
     * @return array
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function updateProgramme(Request $request, $programmeId)
    {

        $authHeader = $request->headers->get('Authorization');
        $scope = $this->getCurrentUserScope($request);
        $user = $this->getCurrentUserObj($request);

        $paramList = "name,code,welcome,link_webmail,link_yammer,link_myesuite,link_learninghub,link_faculty_blog,link_knowledge,link_amphihq,published,private,starts_on_sunday,company_logo,company_logo_size,with_discussions,discussions_publish,learning_journey";
        $data = $this->loadDataFromRequest( $request, $paramList );

        $this->log("UPDATING PROGRAMME:".$programmeId);

        // Find the Programme
        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme not found');
        }
        $learningJourneyStatus=$programme->getLearningJourney();
       
        if(!$learningJourneyStatus)
        {
            
            $result = $this->s3->removeFromS3(
                "learning-journey/" . $programme->getId() . ".pdf",
                true,
                $this->s3->backupBucket
            );
            if ($result)
            {
                $this->log('Learning Journey Deleted');
            }
            else{
                $this->log('Learning Journey Failed');
            }
        }

        if($programme->getArchived()) {
            $this->log('Programme not found');
            throw new InvalidResourceException(['You are not allowed to update the Programme. Programme has been marked as archived.']);
        }

        $programme->setRequestorId($user->getId());

        $this->checkReadWriteAccessToProgramme($request,$programme);

        $programme = $this->processProgramme( $programme, $data );

        $this->updateRecord(self::$ENTITY_NAME, $programme);

        $this->log( "Programme " . $programmeId . " is updated");

        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        //check if user has access to the programme
        $programme->setRequestorId($user->getId());
        $programme->setRequestorScope($scope);

        // push notification
        $this->notify->setLogUuid($request);
        foreach($programme->getCourses() as $course) {
            $this->notify->message($course, self::$ENTITY_NAME);
        }

        $this->log( "Notification send for Programme " . $programmeId );

        //submit a profile refresh for profile-book by uploading a json file
        /** @var UserToken $serviceToken */
        $serviceToken = $this->login->generateServiceToken( $authHeader );
        if( $serviceToken ) {
            $this->log("Triggering a generation for session sheets under programme " . $programmeId);
            $programmeData = ["programme_id" => $programmeId, "service_token" => $serviceToken->getOauthAccessToken()];

            $result = $this->s3->uploadToS3(
                "preprocessed-resources/raw-programme-sheets/" . $programmeId . ".json",
                json_encode($programmeData),
                true
            );

            $this->log( "Programme-session sheets result [" . $programmeId . "]:" . json_encode($result) );
        }

        return [strtolower(self::$ENTITY_NAME) => $programme];
    }

    /**
     * Function to delete an existing Programme
     *
     * @param Request $request Request Object
     * @param String $programmeId id of the Programme
     *
     * @return Response
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function deleteProgramme(Request $request, $programmeId)
    {

        $user = $this->getCurrentUserObj($request);

        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme not found');
        }

        $this->checkReadWriteAccessToProgramme($request,$programme);

        $superAdmins = [];
        if( $this->superAdminList ) {
            $superAdmins = explode(",",(string) $this->superAdminList);
        }

        $super = false;
        $owner = false;

        if (array_search($user->getPeoplesoftId(), $superAdmins) !== false) {
            $super = true;
        }

        if (!$super) {
            $programme->setRequestorId($user->getId());
            $owner = $programme->checkIfOwner();
        }

        if( $super || $owner ) {
            $em = $this->entityManager;

            /** @var Course $course */
            foreach($programme->getCourses() as $course) {
                // Delete Course's default group
                $courseDefaultGroupA = $this->entityManager
                    ->getRepository(Group::class)
                    ->findBy(['course_default' => TRUE, 'course' => $course->getId()]);

                if(count($courseDefaultGroupA) > 0) {
                    // This Course has a default Group
                    $courseDefaultGroup = $courseDefaultGroupA[0];

                    $em->remove($courseDefaultGroup);

                    $responseObj = new Response();
                    $responseObj->setStatusCode(204);
                }
            }

            /** @var ProgrammeAdministrator $admins */
            foreach($programme->getProgrammeAdministrators() as $admin) {
                $em->remove($admin);
            }

            $em->remove($programme);
            $em->flush();

            $responseObj = new Response();
            $responseObj->setStatusCode(204);
        } else {
            throw new InvalidResourceException(["You are not allowed to delete this programme"]);
        }

        return $responseObj;
    }

    /**
     * Function to retrieve courses belonging to an existing Programme
     *
     * @param Request       $request            Request Object
     * @param String        $programmeId        id of the Programme
     *
     * @throws ResourceNotFoundException
     * @throws ForbiddenException
     *
     * @return array
     */
    public function getCoursesFromProgramme(Request $request, $programmeId)
    {

        $this->log('Getting Courses for Programme: ' . $programmeId);

        $userId = $this->getCurrentUserId($request);

        $sessionsWithPublishedHandouts = $this->getSessionsWithPublishedHandouts($this->getCurrentUserObj($request));

        $courses = $this->getPublishedSubscribedCourses($request,$programmeId);

        if (sizeof($courses) <= 0) {
            throw new ForbiddenException();
        }

        $courseViewTypeForCourse = [];
        /** @var Course $course */
        foreach ($courses as $course) {
            $course->getProgramme()->setRequestorId($userId);
            $course->getProgramme()->setForParticipant(true);
            $course->getProgramme()->setIncludeHidden(true);

            // Serialize entire sub-entities
            $course->serializeFullObject(TRUE);
            // Serialize only published sub-entities as this endpoint is used by mimstudent role
            $course->serializeOnlyPublished(TRUE);
            // Serialize tasks which have at least one subtask
            $course->showOnlyTasksWithSubtasks(TRUE);

            $courseViewType = match ($course->getProgramme()->getViewType()) {
                3 => $course->getCourseTypeView(),
                default => $course->getProgramme()->getViewType(),
            };

            $courseViewTypeForCourse[] = [
                "courseid" => $course->getId(),
                "courseViewType" => $courseViewType
            ];

            if ($course->getCourseTypeView() === null) {
                $course->setCourseTypeView($courseViewType);
            }

            // Get all session content and check publish date
            /** @var Session $session */
            foreach($course->getSessions() as $session) {
                $this->log('Setting flag to serialize only published attachments');

                // Set the flag that will determine whether an attachment needs to be serialized or not
                $session->setSerializeOnlyPublishedAttachments();
                $session->serializeOnlyPublished(TRUE);
                $session->doNotShowGroupSessions(TRUE);

                if ($courseViewType === 1) //Enable to add group session for tile view
                {
                    $session->doNotShowGroupSessions(FALSE);
                    $session->setWebView(TRUE);
                }

                //added to check the GroupSessionAttachment information based on the current user
                $session->checkGroupSessionAttachmentsFor($this->getCurrentUserObj($request)->getPeoplesoftId());

                if(in_array($session->getId(), $sessionsWithPublishedHandouts)) {
                    $session->showHandouts(TRUE);
                }
            }

            /** @var Activity $activity */
            foreach($course->getActivities() as $activity) {
                $activity->serializeOnlyPublished(TRUE);
                $activity->doNotShowGroupActivities(TRUE);
            }
        }

        $scope = $this->getCurrentUserScope($request);
        if ($scope !== "mimstudent" && $scope !== "edotstudent"){
            return ['courses' => $courses];
        } else {
            $serializer = SerializerBuilder::create()->build();
            $serializedCourse = $serializer->toArray($courses);
            $courses = null;

            foreach ($serializedCourse as $keyCourse => $course) {
                $sessionsToUnset = [];

                $key = array_search($course["id"], array_column($courseViewTypeForCourse, 'courseid'));
                $courseViewType = $courseViewTypeForCourse[$key]['courseViewType'];
                $serializedCourse[$keyCourse]['course_type_view'] = $courseViewType;
                foreach ($course['sessions'] as $keySession => $session) {

                    $qb = $this->entityManager->createQueryBuilder();
                    $qb->select('cs')
                        ->from(CourseSubscription::class, 'cs')
                        ->join(Course::class, 'c', Join::WITH, 'c.id = cs.course')
                        ->join(Session::class, 's', Join::WITH, 'c.id = s.course')
                        ->join(Group::class, 'g', Join::WITH, 'g.course = c.id')
                        ->join('g.group_sessions', 'gs', Join::WITH, 'g.id = gs.group and gs.session = s.id')
                        ->join('g.group_members', 'gu', Join::WITH, 'gu.id = cs.user')
                        ->where('c.id = :courseId and cs.user = :userId and s.id = :sessionId')
                        ->setParameter('courseId', $course['id'])
                        ->setParameter('userId', $userId)
                        ->setParameter('sessionId', $session['id']);

                    if (!array_key_exists('course_type_view',$course)) {
                        $course['course_type_view'] = $courseViewType;
                    }

                    if (!$qb->getQuery()->getResult()) {
                        // check if course is tile view
                        if ($course['course_type_view'] === 0) array_push($sessionsToUnset, $keySession);
                    }
                }

                foreach ($sessionsToUnset as $keyIndex) {
                    unset($serializedCourse[$keyCourse]['sessions'][$keyIndex]);
                }

                $serializedCourse[$keyCourse]['sessions'] = array_values($serializedCourse[$keyCourse]['sessions']);
            }

            return ['courses' => $serializedCourse];
        }
    }

    /**
     * Function to retrieve users belonging to an existing Programme
     *
     * @param Request $request Request Object
     * @param String $programmeId id of the Programme
     *
     * @return array
     */
    public function getUsersFromProgramme(Request $request, $programmeId)
    {

        $subscriptions = $this->entityManager
            ->getRepository(CourseSubscription::class)
            ->findBy(['programme' => $programmeId]);

        // return empty json if no subscribers to the programme
        if (sizeof($subscriptions) == 0) {
            return ["profiles" => []];
        }

        $profiles = [];
        /** @var CourseSubscription $subscription */
        foreach ($subscriptions as $subscription) {
            $peoplesoftId = $subscription->getUser()->getPeoplesoftId();
            array_push($profiles, $this->getUserProfileData($request, $peoplesoftId));
        }

        return ["profiles" => $profiles];
    }


    /**
     * Function to retrieve users belonging to an existing Programme
     *
     * @param Request       $request            Request Object
     * @param String        $programmeId        id of the Programme
     *
     * @return array
     */
    public function getCoordinatorsFromProgramme(Request $request, $programmeId)
    {

        $show = $request->query->get("show");

        $superAdmins = [];
        if( $this->superAdminList ) {
            $superAdmins = explode(",",(string) $this->superAdminList);
        }

        $criteria = new Criteria();
        $expr = $criteria->expr();
        $criteria->where(
            $expr->eq('blocked',false)
        );
        $criteria->orWhere(
            $expr->eq('faculty',true)
        );

        $admins = $this->entityManager
            ->getRepository(Administrator::class)
            ->matching($criteria);

        $programmeAdmins = $this->entityManager
            ->getRepository(ProgrammeAdministrator::class)
            ->findBy(["programme"=>$programmeId]);

        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(["id"=>$programmeId]);

        $programmeTeam = $programme->getProgrammeTeam();

        $coordinators = [];
        $coordinatorIds = [];
        /** @var Administrator $admin */
        foreach ($admins as $admin) {

            $super = false;
            $owner = false;
            $progAdmin = false;

            if( array_search($admin->getPeoplesoftId(),$superAdmins) !== false ) {
                $super = true;
                $progAdmin = true;
            }

            if( !$super ) {
                /** @var ProgrammeAdministrator $pAdmin */
                foreach ($programmeAdmins as $pAdmin) {
                    if ($pAdmin->getUser()->getPeoplesoftId() == $admin->getPeoplesoftId()) {

                        $owner = $pAdmin->getOwner();
                        $progAdmin = true;

                        break;
                    }
                }

            }

            $adminInfo = ["peoplesoft_id" => $admin->getPeoplesoftId(), "is_faculty" => $admin->getFaculty(), "is_super" => $super, "is_admin" => $progAdmin, "is_owner" => $owner];

            if( $show == "blocked" ) {
                if( !$progAdmin && array_search($admin->getPeoplesoftId(), $programmeTeam) === false ) {
                    array_push($coordinators, $adminInfo);
                }

            } else {
                //programme team members are process separately
                if ( $super || $owner || array_search($admin->getPeoplesoftId(), $programmeTeam) === false ) {
                    //show all when no parameter is given
                    //show team only
                    if(
                        is_null($show)
                        || ( !is_null($show) && $show == "all" )
                        || ( !is_null($show) && $show == "team" && $progAdmin )
                    ) {
                        array_push($coordinators, $adminInfo);
                        array_push($coordinatorIds, $admin->getPeoplesoftId());
                    }
                }
            }
        }

        if( $show != "blocked" ) {
            foreach ($programmeTeam as $member) {
                $owner = false;
                /** @var ProgrammeAdministrator $pAdmin */
                foreach ($programmeAdmins as $pAdmin) {
                    if ($pAdmin->getUser()->getPeoplesoftId() == $member) {
                        $owner = $pAdmin->getOwner();
                        break;
                    }
                }

                $adminInfo = ["peoplesoft_id" => $member, "is_admin" => true, "is_owner" => $owner, "is_team_member" => true];

                if (array_search($member, $coordinatorIds) === false) {
                    array_push($coordinators, $adminInfo);
                }
            }
        }

        return ["coordinators" => $coordinators];
    }

    /**
     * Function to update administrators of the programme
     *
     * @param Request $request Request Object
     * @param String $programmeId id of the Programme
     *
     * @return array
     * @throws ResourceNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateCoordinatorsFromProgramme(Request $request, $programmeId)
    {

        $adminPsoftId = $request->get('peoplesoft_id');
        $action = $request->get('action');

        $this->log('Trying to ' . $action . ' admin access of [' . $adminPsoftId . '] from Programme ' . $programmeId);

        $em = $this->entityManager;

        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(["id"=>$programmeId]);

        if( !$programme ) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme not found');
        }

        /** @var Administrator $admin */
        $admin = $this->entityManager
            ->getRepository(Administrator::class)
            ->findOneBy(["peoplesoft_id"=>$adminPsoftId]);

        if( !$admin ) {
            $this->log('Admin not found');
            throw new ResourceNotFoundException('Admin not found');
        }

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(["peoplesoft_id"=>$admin->getPeoplesoftId()]);

        if( !$user ) {
            $user = new User();
            $user->setPeoplesoftId($adminPsoftId);

            $em->persist($user);
        }

        $programmeAdmin = $this->entityManager
            ->getRepository(ProgrammeAdministrator::class)
            ->findOneBy(["programme" => $programmeId, "user" => $user->getId()]);

        if( $action == "allow" ) {
            if( !$programmeAdmin && (!$admin->getBlocked() || $admin->getFaculty()) ) {
                $programmeAdmin = new ProgrammeAdministrator();
                $programmeAdmin->setProgramme($programme);
                $programmeAdmin->setUser($user);

                $em->persist($programmeAdmin);
            }
        } else if( $action == "revoke" ) {
            if( $programmeAdmin ) {
                $em->remove($programmeAdmin);
            }
        }

        $em->flush();

        return ["coordinators"=>[
            "message" => $action . " admin access was processed for " . $adminPsoftId
        ]];
    }

    /**
     * Handler to switch programme archive
     *
     * @param $programmeId
     *
     * @return array
     *
     * @throws ForbiddenException
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     *
     */
    public function archiveProgramme(Request $request,$programmeId){

        $this->log('Archiving Programme: ' . $programmeId);

        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(["id"=>$programmeId]);

        if( !$programme ) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme not found');
        }

        $this->checkReadWriteAccessToProgramme($request,$programme);

        $scope = $this->getCurrentUserScope($request);

        if ( $scope == "edotadmin" || $scope == "edotsuper" || $programme->checkIfMy()) {
            if ($programme->getAllowedToArchive()) {
                $currentDayWithAddition = new \DateTime();
                $currentDayWithAddition->add(new \DateInterval('P30D'));
                $currentDayWithAddition->setTime(0,0,0);
                $programme->setArchived(!$programme->getArchived());
                $programme->setArchiveDate($currentDayWithAddition);

                $this->entityManager->persist($programme);
                $this->entityManager->flush();

                return $this->getProgramme($request, $programmeId);
            } else {
                throw new InvalidResourceException(['Programme are not allowed to Archive. Programme end date is not yet 18 months']);
            }
        } else {
            throw new InvalidResourceException(['You are not allowed to Archive this programme']);
        }

    }

    /**
     * Handler for list archived programme
     *
     * @return array[]
     * @throws Exception
     */
    public function archiveProgrammeList(Request $request){
        $userId = $this->getCurrentUserId($request);
        $scope = $this->getCurrentUserScope($request);

        $items = $this->entityManager
            ->getRepository(Programme::class)
            ->findBy(['archived' => 1]);

        $programmes = [];
        /** @var Programme $programme */
        foreach( $items as $programme ) {
            $toInclude = true;
            $programme->setRequestorId($userId);
            $programme->setIncludeHidden(true);

            $programme->setRequestorScope($scope);

            //for normal admin, only show their programme and other published non-private programmes
            if ($scope != "edotsuper") {
                $toInclude = $programme->checkIfMy() || (!$programme->checkIfMy() && !$programme->getPrivate() && $programme->getPublished());
            }

            if ($programme->getArchivedRemainingDays() > 0) {
                if ($toInclude) {
                    array_push($programmes, $programme);
                }
            }
        }

        return ['programmes' => $programmes];
    }

    /**
     * Function to generate json object then upload to S3 to initiate the copy programme
     *
     * @param Request $request Request Object
     * @param String $programmeId id of the Programme
     *
     * @return array
     *
     * @throws InvalidResourceException
     */
    public function copyProgramme(Request $request, $programmeId)
    {
        $newProgramme  = trim((string) $request->get('newName'));
        $peoplesoft_id = trim((string) $request->get('peoplesoft_id'));
        $courses       = $request->get('courses');
        $type_view_old       = $request->get('type_view');
        $this->log("ID: {$programmeId} - {$newProgramme}");
        $this->log("Courses ".json_encode($courses));
        $programme = $this->entityManager
        ->getRepository(Programme::class)
        ->findOneBy(["id" => $programmeId]);
        $view_type_existing = $programme->getViewType();
        $this->log("Type View {$view_type_existing}");

        if (!$newProgramme || strlen($newProgramme) < 0) {
            $this->log("The new programme name is missing");
            throw new InvalidResourceException(['The new programme name is missing']);
        }

        if (!$peoplesoft_id) {
            $this->log("Requester peoplesoft id is missing");
            throw new InvalidResourceException(['Requester peoplesoft id is missing']);
        }

        $s3Payload = [
            'peoplesoft_id' => $peoplesoft_id,
            'programmeId'   => $programmeId,
            'programmeName' => $newProgramme,
            'courses'       => $courses
        ];

        try {
            $result = $this->s3->uploadToS3(
                "copy-programme/" . $programmeId.".".date("YmdHis").".".microtime(true). ".json",
                json_encode($s3Payload),
                true
            );

            if ($result) {
                $this->log( "(copyProgramme) Successfully uploaded S3 file [" . $programmeId . "]:" . json_encode($result) );
            } else {
                $this->log("(copyProgramme) Unable to upload to S3");
            }
        } catch (Exception $e) {
            $this->log("(copyProgramme) Unable to upload to S3 " . $e->getMessage());
            throw new InvalidResourceException(['Unable to upload to S3']);
        }

        return ['success' => 'success'];
    }

    /**
     * Function to copy an existing Programme to create a new one base from request
     *
     * @param Request $request Request Object
     *
     * @return mixed
     *
     * @throws InvalidResourceException
     * @throws Exception
     */
    public function initiateCopyProgramme(Request $request) {
        $payload       = json_decode($request->getContent(), true);
        $programmeId   = $payload['programmeId'];
        $peoplesoft_id = $payload['peoplesoft_id'];
        $programmeName = $payload['programmeName'];
        $courses       = $payload['courses'];

        $this->copyProgrammeTransactionId = "cp.{$peoplesoft_id}.{$programmeId}.".mktime(date("H"));

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(["peoplesoft_id" => $peoplesoft_id]);

        $this->copyProgrammeLogger("Request payload: ".print_r($payload, true));
        if (!$user) {
            $this->copyProgrammeLogger("Copy programme unable to find user with peoplesoft id: $peoplesoft_id");
            throw new InvalidResourceException(['Unable to find user ('.$peoplesoft_id.')']);
        }

        /** @var Programme $programme */
        $programme = $this->entityManager
            ->getRepository(Programme::class)
            ->findOneBy(["id" => $programmeId]);

        if (!$programme) {
            $this->copyProgrammeLogger("Unable to find programme id: $programmeId");
            throw new InvalidResourceException(['Unable to find programme ('.$programmeId.')']);
        }

        $programme->setOverriderReadonly(true);
        $newDetails = ['name' => $programmeName];
        $newProgramme = null;
        try {
            /**
             * Clone Programme
             */
            $newProgramme = $this->cloneProgramme($programme, $user, $newDetails);

            /**
             * Clone Course
             */
            foreach ($courses as $courseToCopy) {
                if (!array_key_exists('isIncluded', $courseToCopy)) continue;
                if (!$courseToCopy['isIncluded']) continue;

                $this->cloneCourse($newProgramme, $programme, $courseToCopy['course'], ['offsetDays' => $courseToCopy['offsetDays']]);
                $this->copyProgrammeAddResult(true, "-");
            }
        } catch (Exception $e) {
            $this->copyProgrammeLogger("There was an error completing the copy programme. ".$e->getMessage());
        }

        $this->sendEmailValidationForCopyProgramme($user, $programme, $newProgramme, $courses);
        return $this->copyResult;
    }

    /**
     * Function to send email validation after copy programme
     *
     * @param User $user User that initiate the copy programme
     * @param Programme $programme Original Programme
     * @param Programme $newProgramme Replica
     * @param array $copyCourseDetails The details of course to be copied this is from the payload coming from lambda
     *
     * @return void
     * @throws Exception
     */
    private function sendEmailValidationForCopyProgramme(User $user, Programme $programme, Programme $newProgramme, array $copyCourseDetails): void {
        $newProgramme->setOverriderReadonly(true);
        $programme->setOverriderReadonly(true);

        $html_body = "Dear ".strtoupper($user->getLastname())." ".$user->getFirstname().", <br/><br/>";
        $html_body.= "You have successfully copied the <strong>".$programme->getName()."</strong> as a new platform with the following details:<br/><br/>";
        $html_body.= "<strong>New Programme name: </strong>".$newProgramme->getName()."<br/>";

        if (count($newProgramme->getCourses()) > 0) {
            $html_body.="<strong>Course(s):</strong><br/>";
        }

        /** @var Course $course */
        foreach ($newProgramme->getCourses() as $course) {
            $html_body.= "&nbsp;&nbsp;&nbsp;&nbsp;".$course->getName().", ".$course->getStartDate()->format('d-m-Y')."<br/>";
        }

        $html_body.="<br/>";

        if($this->symfony_environment === 'dev' || $this->symfony_environment === 'int' || $this->symfony_environment === 'uat') {
            $keyEnv = match ($this->symfony_environment) {
                'int', 'uat' => "https://edot-admin-".$this->symfony_environment.".esuite.edu",
                default => "http://localhost:4200",
            };
            $html_body .= "URL from edot Admin: $keyEnv/programmes/" . $newProgramme->getId()."<br/><br/>";
        } else {
            $html_body .= "URL from edot Admin: https://edot-admin.esuite.edu/programmes/" . $newProgramme->getId()."<br/><br/>";
        }

        $html_body.="Please note that the following elements will not be included in the copy:<br/>";
        $html_body.="<ul>";
        $html_body.="<li>Class number and term – these need to be completed with the new details</li>";
        $html_body.="<li>Participant enrolment – this needs to be done according to the new class number/term</li>";
        $html_body.="<li>Session documents & handouts – these need to be added with relevant copyright permissions</li>";
        $html_body.="</ul>";

        $html_body.="<br/>";

        $html_body.="By default, each course will be copied as <strong><em>unpublished</em></strong>. Before publishing your new platform, <strong>be sure to check the following areas carefully, in particular if there are links or any reference to dates:</strong>";
        $html_body.="<ul>";
        $html_body.="<li>Course dates, location, time-zone</li>";
        $html_body.="<li>Welcome email text, display and logo (if applicable)</li>";
        $html_body.="<li>To Do list deadlines and next steps</li>";
        $html_body.="<li>Session timing, location, host and description</li>";
        $html_body.="<li style=\"color: red; font-weight: bold;\">Refresh the People page for each course to see enrolments carried over</li>";
        $html_body.="<li>People page enrolment</li>";
        $html_body.="<li>Participant enrolment to be added according to the new class number</li>";
        $html_body.="<li>Huddle space discussion threads and groups to be added (if applicable)</li>";
        $html_body.="</ul>";

        $html_body.="<br/>";

        $html_body.="We recommend that you review the whole platform in detail before final launch.<br/><br/>";
        $html_body.="Best regards,<br/>";
        $html_body.="Your edot team<br/><br/><br/><br/><br/>";
        $html_body.="Request Details: <br/>";
        $html_body.="*** <em>Transaction ID: ".$this->copyProgrammeTransactionId."</em><br/><br/>";
        $html_body.="<strong>Programme Name: </strong>".$newProgramme->getName()."<br/><br/>";
        $html_body.="<strong>List of Courses to be copied:</strong><br/>";

        foreach ($copyCourseDetails as $courseToCopy) {
            if (!array_key_exists('isIncluded', $courseToCopy)) continue;
            if (!$courseToCopy['isIncluded']) continue;

            $offsetDays = $courseToCopy['offsetDays'];
            $newStartDate = new \DateTime($courseToCopy['newStartDate']);
            $newStartDate->setTimezone(new \DateTimeZone('UTC'));

            $start_date = new \DateTime($courseToCopy['start_date']);
            $start_date->setTimezone(new \DateTimeZone('UTC'));

            $end_date = new \DateTime($courseToCopy['end_date']);
            $end_date->setTimezone(new \DateTimeZone('UTC'));

            $difference = $start_date->diff($end_date);
            $interval = $difference->format("%r%a");

            $html_body.="<strong>".$courseToCopy['name']."</strong><br/>";
            $html_body.="<strong>Start Date: </strong>".$newStartDate->format("d M Y")."<br/>";
            $html_body.="<strong>End Date: </strong>".$newStartDate->add(new \DateInterval("P{$interval}D"))->format("d M Y")."<br/>";
            $html_body.="<strong>Offset days: </strong>".$offsetDays."<br/>";
            $html_body.="<br/>";
        }

        $char_set = 'UTF-8';
        $subject = 'edot@esuite Copy Programme email notification';
        if( isset($this->symfony_environment) && ($this->symfony_environment === 'dev' || $this->symfony_environment === 'int' || $this->symfony_environment === 'uat')) {
            $subject = '(TEST / '.$this->symfony_environment.') '.$subject;
        }

        /** @var UserProfileCache $userProfileCache */
        $userProfileCache = $user->getUserProfileCache();
        $recipient_emails = [$userProfileCache->getUpnEmail()];

        $sender_email = $this->aws_ses_from_email;

        try {
            $result = $this->sesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => $recipient_emails,
                ],
                'ReplyToAddresses' => [$sender_email],
                'Source' => $sender_email,
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => $char_set,
                            'Data' => $html_body,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => $char_set,
                        'Data' => $subject,
                    ],
                ],
            ]);
            $messageId = $result['MessageId'];
            $this->log("Email sent! Message ID: $messageId"."\n");
        } catch (AwsException $e) {
            // output error message if fails
            $this->log($e->getMessage());
            $this->log("The email was not sent. Error message: ".$e->getAwsErrorMessage()."\n");
        }
    }

    /**
     * Method to copy a programme entity into a new one
     * This will persist the data, saves the data to the database
     *
     * @param Programme  $programme  Programme to copy
     * @param User       $user       The owner of the programme
     * @param array|null $newDetails Key-value pair to set new details of the programme
     *
     * @return Programme
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function cloneProgramme(Programme $programme, User $user, array $newDetails = null): Programme
    {
        $newProgramme = clone $programme;

        $this->copyProgrammeLogger("Creating new Programme");
        if ($newDetails) {
            if (array_key_exists('name', $newDetails)){
                $newProgramme->setName($newDetails['name']);
            }
        }

        $this->entityManager->persist($newProgramme);
        $this->entityManager->flush();

        $this->copyProgrammeLogger("Done Creating new Programme ({$newProgramme->getId()})");
        $this->copyProgrammeAddResult(true, "Programme details has been copied");

        $this->copyProgrammeLogger("Adding user to the copied programme ({$newProgramme->getId()})");
        /**
         * Add the requester to the new programme as the owner
         */
        $programmeAdmin = new ProgrammeAdministrator();
        $programmeAdmin->setProgramme($newProgramme);
        $programmeAdmin->setUser($user);
        $programmeAdmin->setOwner(true);
        $this->entityManager->persist($programmeAdmin);
        $this->entityManager->flush();

        $this->copyProgrammeLogger("Done adding user to the copied programme ({$newProgramme->getId()})");
        $this->copyProgrammeAddResult(true, "\t* Requestor has been set as programme owner");

        if ($newProgramme->getCompanyLogo()) {
            $this->copyProgrammeLogger("Company logo is enable for programme ({$newProgramme->getId()}). Start copying");
            $copyLogoResponse = $this->s3->copyExistingItemToS3(
                "programme-company-logo-inline-style/" . $newProgramme->getId() . ".svg",
                "programme-company-logo-inline-style/" . $programme->getId() . ".svg",
                true,
            );

            if (array_key_exists('status', $copyLogoResponse)) {
                if ($copyLogoResponse['status'] === 'success') {
                    $this->copyProgrammeAddResult(true, "\t* Programme logo has been copied");
                    $this->copyProgrammeLogger('Programme logo successfully copied');
                } else {
                    $this->copyProgrammeAddResult(false, "\t* Unable to copy Programme logo");
                    $this->copyProgrammeLogger('Programme logo unable to determine status. '.json_encode($copyLogoResponse));
                }
            } else {
                $this->copyProgrammeAddResult(false, "\t* Unable to copy Programme logo");
                $this->copyProgrammeLogger('Unable to copy programme logo. '.$copyLogoResponse['error']);
            }
        } else {
            $this->copyProgrammeAddResult(false, "\t* Programme logo is not enabled");
            $this->copyProgrammeLogger("Company logo is not enabled for programme ({$newProgramme->getId()}). Skipping");
        }

        return $newProgramme;
    }

    /**
     * Method to copy a programme entity into a new one
     * This will persist the data, saves the data to the database
     *
     * @param Programme $programme Programme to where the course will be saved
     * @param int $courseId CourseId to copy
     * @param array|null $newDetails Key-value pair to set new details of the programme
     *
     * @return Course|null
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function cloneCourse(Programme $programme, Programme $oldProgramme, int $courseId, array $newDetails = null): Course|null
    {
        $offsetDays = 0;
        if ($newDetails) {
            if (array_key_exists('offsetDays', $newDetails)) {
                $offsetDays = $newDetails['offsetDays'];
            }
        }

        /** @var Course $courseObj */
        $courseObj = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(["id" => $courseId]);

        $newCourse = null;
        if (!$courseObj) {
            $this->copyProgrammeAddResult(false, "Unable to copy course with id: {$courseId}");
            $this->copyProgrammeLogger("Copy programme unable to find course id: {$courseId}");
        } else {
            $this->copyProgrammeLogger("Copying course: {$courseObj->getName()} {$courseId}");
            $newCourse = clone $courseObj;
            $newCourse->setProgramme($programme);

            $courseStartDate = $courseObj->getStartDate();
            $courseStartDate->setTimezone(new \DateTimeZone('UTC'));
            $newStartDate = $courseStartDate->add(new \DateInterval("P{$offsetDays}D"))->setTimezone(new \DateTimeZone('UTC'));
            $newCourse->setStartDate($newStartDate);

            $courseEndDate = $courseObj->getEndDate();
            $courseEndDate->setTimezone(new \DateTimeZone('UTC'));
            $newEndDate = $courseEndDate->add(new \DateInterval("P{$offsetDays}D"))->setTimezone(new \DateTimeZone('UTC'));
            $newCourse->setEndDate($newEndDate);
            if($oldProgramme->getViewType() === 3){
                if (!is_null($courseObj->getCourseTypeView())) {
                    $newCourse->setCourseTypeView($courseObj->getCourseTypeView());
                    $this->copyProgrammeLogger("Copy Programme - setting With Type View (logid-1): ".$courseObj->getCourseTypeView());
                } else {
                    $newCourse->setCourseTypeView($oldProgramme->getViewType());
                    $this->copyProgrammeLogger("Copy Programme - setting  With Type View (logid-2): " . $oldProgramme->getViewType());
                }
            } else {
                $newCourse->setCourseTypeView($oldProgramme->getViewType());
                $this->copyProgrammeLogger("Copy Programme - setting  With Type View (logid-3): " . $oldProgramme->getViewType());
            }
            

            $this->entityManager->persist($newCourse);
            $this->entityManager->flush();

            $this->entityManager->refresh($courseObj);
            $this->entityManager->refresh($newCourse);

            $this->copyProgrammeLogger("Offset: {$offsetDays} start_date: {$courseObj->getStartDate()->setTimezone(new \DateTimeZone($courseObj->getTimezone()))->format("Y-m-d")} to new start_date: {$newCourse->getStartDate()->setTimezone(new \DateTimeZone($newCourse->getTimezone()))->format("Y-m-d")}");
            $this->copyProgrammeLogger("Offset: {$offsetDays} end_date: {$courseObj->getEndDate()->setTimezone(new \DateTimeZone($courseObj->getTimezone()))->format("Y-m-d")} to new end_date: {$newCourse->getEndDate()->setTimezone(new \DateTimeZone($newCourse->getTimezone()))->format("Y-m-d")}");
            $this->copyProgrammeAddResult(true, "Course ({$newCourse->getName()}) has been created with dates from {$newCourse->getStartDate()->setTimezone(new \DateTimeZone($newCourse->getTimezone()))->format("d-M-Y")} to {$newCourse->getEndDate()->setTimezone(new \DateTimeZone($newCourse->getTimezone()))->format("d-M-Y")}");

            $this->log("Creating default group for course({$newCourse->getId()})");
            // Create default group for Course
            $group = new Group();
            $group->setCourse($newCourse);
            $group->setName('Everyone');
            $group->setColour(-1);
            $group->setStartDate($newCourse->getStartDate()->setTimezone(new \DateTimeZone('UTC')));
            $group->setEndDate($newCourse->getEndDate()->setTimezone(new \DateTimeZone('UTC')));

            $group->setCourseDefault(TRUE);
            $this->entityManager->persist($group);
            $this->entityManager->flush();

            $this->copyProgrammeGroupMapping['Everyone'] = $group;
            $this->copyProgrammeLogger("Done creating default group({$group->getId()}) for course({$newCourse->getId()})");

            try {
                foreach (['coordinator', 'director', 'faculty', 'esuiteteam', 'contact', 'hidden'] as $roleKey) {
                    $this->cloneCourseSubscriptions($courseObj, $newCourse, $roleKey);
                }
                $this->copyProgrammeAddResult(true, "\t* Users has been copied");
                $this->copyProgrammeLogger("Users has been copied");
            } catch (Exception $e) {
                $this->copyProgrammeAddResult(true, "\t* Unable to copy all users");
                $this->copyProgrammeLogger("Unable to complete the copying of User course subscriptions. ".$e->getMessage());
            }

            try {
                $this->cloneSections($courseObj, $newCourse, $newDetails);
                $this->copyProgrammeAddResult(true, "\t* Sections has been copied");
                $this->copyProgrammeLogger("Sections has been copied");
            } catch (Exception $e) {
                $this->copyProgrammeAddResult(false, "\t* Unable to copy all Sections");
                $this->copyProgrammeLogger("Unable to complete the copying of Sections. ".$e->getMessage());
            }

            try {
                $this->cloneToDo($courseObj, $newCourse, $newDetails);
                $this->copyProgrammeAddResult(true, "\t* To-Do has been copied");
                $this->copyProgrammeLogger("Todo has been copied");
            } catch (Exception $e) {
                $this->copyProgrammeAddResult(false, "\t* Unable to copy all Todo");
                $this->copyProgrammeLogger("Unable to complete the copying of Todo. ".$e->getMessage());
            }

            try {
                $this->cloneSessions($courseObj, $newCourse, $newDetails);
                $this->copyProgrammeAddResult(true, "\t* Sessions has been copied");
                $this->copyProgrammeLogger("Sessions has been copied");
            } catch (Exception $e) {
                $this->copyProgrammeAddResult(false, "\t* Unable to copy all Session");
                $this->copyProgrammeLogger("Unable to complete the copying of Session. ".$e->getMessage());
            }

            try {
                $this->cloneActivities($courseObj, $newCourse, $newDetails);
                $this->copyProgrammeAddResult(true, "\t* Activities has been copied");
                $this->copyProgrammeLogger("Activities has been copied");
            } catch (Exception $e) {
                $this->copyProgrammeAddResult(false, "\t* Unable to copy all Activity");
                $this->copyProgrammeLogger("Unable to complete the copying of Activity. ".$e->getMessage());
            }
        }

        usleep(1000000);
        return $newCourse;
    }

    /**
     * Handler function to copy the users from old course to the new course
     * @param Course $course The origin course to where to get the users
     * @param Course $newCourse The destination course where to add the users
     * @param string $roleString The role of the user
     * @return void
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function cloneCourseSubscriptions(Course $course, Course $newCourse, string $roleString): void
    {
        $this->copyProgrammeLogger("Copying role: {$roleString} from course({$course->getId()}) to course({$newCourse->getId()})");
        /** @var Role $role */
        $role = $this->entityManager
            ->getRepository(Role::class)
            ->findOneBy(['name' => $roleString]);

        if (!$role) {
            $this->copyProgrammeLogger("Unable to copy users for role: $roleString, role not exists");
            return;
        }

        $listOfUsers = match ($roleString) {
            'coordinator' => $course->getCoordination(true),
            'director' => $course->getDirectors(true),
            'faculty' => $course->getFaculty(true),
            'esuiteteam' => $course->getesuiteTeam(true),
            'contact' => $course->getContacts(true),
            'hidden' => $course->getHidden(true),
            default => [],
        };

        foreach ($listOfUsers as $subscribedUser) {
            /** @var CourseSubscription $subscription */
            $subscription = $this->entityManager
                ->getRepository(CourseSubscription::class)
                ->findOneBy(['user' => $subscribedUser->getId(), 'course' => $newCourse->getId()]);

            if (!$subscription) { // means user is not enrolled
                $subscription = new CourseSubscription();
                $subscription->setCourse($newCourse);
                $subscription->setUser($subscribedUser);
                $subscription->setRole($role);
                $subscription->setProgramme($newCourse->getProgramme());

                $newCourse = $newCourse->addUser($subscription);
                $this->entityManager->persist($newCourse);
                $this->entityManager->persist($subscription);

                // ADD USER TO DEFAULT COURSE GROUP
                $courseDefault = $this->entityManager
                    ->getRepository(Group::class)
                    ->findBy(['course_default' => TRUE, 'course' => $newCourse->getId()]);

                if (count($courseDefault) > 0) {
                    // This Course has a default Group
                    $courseDefaultGroup = $courseDefault[0];
                    $courseDefaultGroup = $courseDefaultGroup->addUser($subscribedUser);
                    $this->entityManager->persist($courseDefaultGroup);
                }
            }
        }

        $this->copyProgrammeLogger("Done copying role: {$roleString} from course({$course->getId()}) to course({$newCourse->getId()})");
        $this->entityManager->flush();
    }

    /**
     * Handler to copy sections from one course to another
     *
     * @param array|null    $newDetails
     *
     * @return void
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function cloneSections(Course $course, Course $newCourse, array $newDetails = null): void
    {
        $offsetDays = 0;
        if ($newDetails) {
            if (array_key_exists('offsetDays', $newDetails)) {
                $offsetDays = $newDetails['offsetDays'];
            }
        }

        $this->copyProgrammeLogger("Copying Sections from course({$course->getId()}) to course({$newCourse->getId()})");

        $allUserExceptStudent = $newCourse->getAllUsers();
        unset($allUserExceptStudent['students']);

        $usersInGroup = [];
        foreach ($allUserExceptStudent as $key => $usersCategory) {
            if (count($usersCategory) > 0) $usersInGroup = array_merge($usersInGroup, $usersCategory);
        }

        $groups = $course->getGroups();

        /** @var Group $group */
        foreach ($groups as $group) {
            if (!$group->getCourseDefault()) {
                $newGroup = clone $group;
                $newGroup->setCourse($newCourse);

                if ($group->getStartDate()) {
                    $newGroupStartDate = $group->getStartDate();
                    $newGroupStartDate->setTimezone(new \DateTimeZone('UTC'));
                    $newGroup->setStartDate($newGroupStartDate->add(new \DateInterval("P{$offsetDays}D"))->setTimezone(new \DateTimeZone('UTC')));
                }

                if ($group->getEndDate()) {
                    $newGroupEndDate = $group->getEndDate();
                    $newGroupEndDate->setTimezone(new \DateTimeZone('UTC'));
                    $newGroup->setEndDate($newGroupEndDate->add(new \DateInterval("P{$offsetDays}D"))->setTimezone(new \DateTimeZone('UTC')));
                }

                $this->entityManager->persist($newGroup);

                $this->copyProgrammeLogger("Adding Users to the group");
                /** @var User $user */
                foreach ($group->getUsers() as $user) {
                    if (in_array($user->getPeoplesoftId(), $usersInGroup)) {
                        $newGroup->addUser($user);
                    }
                }
                $this->entityManager->persist($newGroup);

                $this->copyProgrammeGroupMapping['CP'.$group->getId()] = $newGroup;
                $this->copyProgrammeLogger("Done adding Users to the group");
            }
        }

        $this->entityManager->flush();
        $this->copyProgrammeLogger("Done copying Sections from course({$course->getId()}) to course({$newCourse->getId()})");
    }

    /**
     * Handler to clone To-Dos and all Subtasks
     *
     * @param array|null $newDetails
     *
     * @return void
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function cloneTodo(Course $course, Course $newCourse, array $newDetails = null): void
    {
        $offsetDays = 0;
        if ($newDetails) {
            if (array_key_exists('offsetDays', $newDetails)) {
                $offsetDays = $newDetails['offsetDays'];
            }
        }

        $this->copyProgrammeLogger("Copying ToDo from course({$course->getId()}) to course({$newCourse->getId()})");

        $tasks  = $course->getTasks();
        if ($tasks) {
            /** @var Task $task */
            foreach ($tasks as $task) {
                $newTask = clone $task;
                $newTask->setCourse($newCourse);

                if ($task->getDate()) {
                    $newTaskDate = $task->getDate();
                    $newTaskDate->setTimezone(new \DateTimeZone('UTC'));
                    $newTask->setDate($newTaskDate->add(new \DateInterval("P{$offsetDays}D")));
                }

                $this->entityManager->persist($newTask);

                /**
                 * Copy all Subtasks for a To-Do
                 */

                /** @var Subtask $subtask */
                foreach($task->getSubtasks() as $subtask){
                    if (!$subtask->getEmailSendTo()) { // do not copy email subtask type
                        $newSubtask = clone $subtask;
                        $newSubtask->setTask($newTask);

                        if ($subtask->getUploadToS3() == '1') {
                            $fromPrefix = "document-repository/programme-documents/prog-"
                                . $task->getCourse()->getProgramme()->getId()
                                . "/crs-"
                                . $task->getCourse()->getId()
                                . "/stask-"
                                . $task->getId()
                                . "/";

                            $awsPath = "programme-documents/prog-"
                                . $newTask->getCourse()->getProgramme()->getId()
                                . "/crs-"
                                . $newTask->getCourse()->getId()
                                . "/stask-"
                                . $newTask->getId()
                                . "/";
                            $toPrefix = "document-repository/".$awsPath;

                            $copySubtaskFile = $this->s3->copyExistingItemToS3(
                                $toPrefix.$subtask->getFilename(),
                                $fromPrefix.$subtask->getFilename(),
                                true,
                            );

                            if (array_key_exists('status', $copySubtaskFile)) {
                                if ($copySubtaskFile['status'] === 'success') {
                                    $newSubtask->setAwsPath($awsPath);
                                    $newSubtask->setFileId($copySubtaskFile["data"]["timestamp"]);
                                    $newSubtask->setBoxId($copySubtaskFile["data"]["timestamp"]);
                                }
                            }
                        }

                        $this->entityManager->persist($newSubtask);
                    }
                }
            }
            $this->entityManager->flush();
        } else {
            $this->copyProgrammeLogger("Nothing to copy for ToDo from course({$course->getId()}) to course({$newCourse->getId()})");
        }

        $this->copyProgrammeLogger("Done copying ToDo from course({$course->getId()}) to course({$newCourse->getId()})");
    }

    /**
     * Handler to copy Sessions and schedule
     *
     * @param array|null $newDetails
     * @return void
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function cloneSessions(Course $course, Course $newCourse, array $newDetails = null): void
    {
        $offsetDays = 0;
        if ($newDetails) {
            if (array_key_exists('offsetDays', $newDetails)) {
                $offsetDays = $newDetails['offsetDays'];
            }
        }

        $this->copyProgrammeLogger("Copying Sessions from course({$course->getId()}) to course({$newCourse->getId()})");
        /** @var Session $session */
        foreach($course->getSessions() as $session) {
            usleep(1000000);
            $prevID = $session->getId();
            $newSession = clone $session;
            $newSession->setCourse($newCourse);

            $newUID = strtoupper("S-".$newSession->getAbbreviation()."-".$course->getCountry()."-".date("YmdHis").microtime(true));
            if ($newUID === strtoupper($session->getUid())) {
                $newUID.= mktime(date("H"))."_CP".$prevID;
            }

            $newSession->setUid($newUID);

            if ($session->getStartDate()) {
                $sessionStartDate = $session->getStartDate();
                $sessionStartDate->setTimezone(new \DateTimeZone('UTC'));
                $startDate = $sessionStartDate->add(new \DateInterval("P{$offsetDays}D"));
                $newSession->setStartDate($startDate);
            }

            if ($session->getEndDate()) {
                $sessionEndDate = $session->getEndDate();
                $sessionEndDate->setTimezone(new \DateTimeZone('UTC'));
                $newSession->setEndDate($sessionEndDate->add(new \DateInterval("P{$offsetDays}D")));
            }

            /** @var User $professor */
            foreach ($session->getProfessors() as $professor) {
                $newSession->addProfessor($professor);
            }

            $this->entityManager->persist($newSession);

            /** @var GroupSession $groupSession */
            foreach ($session->getGroupSessions() as $groupSession) {
                $newGroupSession = clone $groupSession;
                $newGroupSession->setDefaults();
                $newGroupSession->setSession($newSession);

                $group = $groupSession->getGroup();
                $groupId = 'Everyone';

                if ($group->getName() !== 'Everyone') {
                    $groupId = 'CP'.$group->getId();
                }
                $setGroup = $this->copyProgrammeGroupMapping[$groupId];

                if ($groupSession->getStartDate()) {
                    $newGroupSessionStartDate = $groupSession->getStartDate();
                    $newGroupSessionStartDate->setTimezone(new \DateTimeZone('UTC'));
                    $newGroupSession->setStartDate($newGroupSessionStartDate->add(new \DateInterval("P{$offsetDays}D")));
                    $newGroupSession->setOriginalStartDate($newGroupSession->getStartDate());
                }

                if ($groupSession->getEndDate()) {
                    $newGroupSessionEndDate = $groupSession->getEndDate();
                    $newGroupSessionEndDate->setTimezone(new \DateTimeZone('UTC'));
                    $newGroupSession->setEndDate($newGroupSessionEndDate->add(new \DateInterval("P{$offsetDays}D")));
                    $newGroupSession->setOriginalEndDate($newGroupSession->getEndDate());
                }

                if ($setGroup) {
                    $newGroupSession->setGroup($setGroup);
                } else {
                    $newGroupSession->setGroup(null);
                }

                $this->entityManager->persist($newGroupSession);
            }
        }

        $this->entityManager->flush();
        $this->copyProgrammeLogger("Done copying Sessions from course({$course->getId()}) to course({$newCourse->getId()})");
    }

    /**
     * Handler to copy Sessions and schedule
     *
     * @param array|null $newDetails
     * @return void
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function cloneActivities(Course $course, Course $newCourse, array $newDetails = null): void
    {
        $offsetDays = 0;
        if ($newDetails) {
            if (array_key_exists('offsetDays', $newDetails)) {
                $offsetDays = $newDetails['offsetDays'];
            }
        }

        $this->copyProgrammeLogger("Copying Activities from course({$course->getId()}) to course({$newCourse->getId()})");
        /** @var Activity $activity */
        foreach($course->getActivities() as $activity) {
            $newActivity = clone $activity;
            $newActivity->setCourse($newCourse);

            if ($activity->getStartDate()) {
                $newActivityStartDate = $activity->getStartDate();
                $newActivityStartDate->setTimezone(new \DateTimeZone('UTC'));
                $newActivity->setStartDate($newActivityStartDate->add(new \DateInterval("P{$offsetDays}D"))->setTimezone(new \DateTimeZone('UTC')));
            }

            if ($activity->getEndDate()) {
                $newActivityEndDate = $activity->getEndDate();
                $newActivityEndDate->setTimezone(new \DateTimeZone('UTC'));
                $newActivity->setEndDate($newActivityEndDate->add(new \DateInterval("P{$offsetDays}D"))->setTimezone(new \DateTimeZone('UTC')));
            }

            $this->entityManager->persist($newActivity);

            /** @var GroupActivity $groupActivity */
            foreach ($activity->getGroupActivities() as $groupActivity) {
                $newGroupActivity = clone $groupActivity;
                $newGroupActivity->setActivity($newActivity);

                $group = $groupActivity->getGroup();
                $groupId = 'Everyone';

                if ($group->getName() !== 'Everyone') {
                    $groupId = 'CP'.$group->getId();
                }
                $setGroup = $this->copyProgrammeGroupMapping[$groupId];

                if ($groupActivity->getStartDate()) {
                    $newGroupActivityStartDate = $groupActivity->getStartDate();
                    $newGroupActivityStartDate->setTimezone(new \DateTimeZone('UTC'));
                    $newGroupActivity->setStartDate($newGroupActivityStartDate->add(new \DateInterval("P{$offsetDays}D"))->setTimezone(new \DateTimeZone('UTC')));
                    $newGroupActivity->setOriginalStartDate($newGroupActivity->getStartDate()->setTimezone(new \DateTimeZone('UTC')));
                }

                if ($groupActivity->getEndDate()) {
                    $newGroupActivityEndDate = $groupActivity->getEndDate();
                    $newGroupActivityEndDate->setTimezone(new \DateTimeZone('UTC'));
                    $newGroupActivity->setEndDate($newGroupActivityEndDate->add(new \DateInterval("P{$offsetDays}D"))->setTimezone(new \DateTimeZone('UTC')));
                    $newGroupActivity->setOriginalEndDate($newGroupActivity->getEndDate()->setTimezone(new \DateTimeZone('UTC')));
                }

                if ($setGroup) {
                    $newGroupActivity->setGroup($setGroup);
                } else {
                    $newGroupActivity->setGroup(null);
                }

                $this->entityManager->persist($newGroupActivity);
            }
        }

        $this->entityManager->flush();
        $this->copyProgrammeLogger("Done copying Activities from course({$course->getId()}) to course({$newCourse->getId()})");
    }

    /**
     * Handler to log copy programme
     *
     * @return void
     */
    private function  copyProgrammeLogger(string $message): void
    {
        $this->log("[cpid: {$this->copyProgrammeTransactionId}]\t[message: {$message}]");
    }

    /**
     * Handler to add copy programme details result
     *
     * @return void
     */
    private function  copyProgrammeAddResult(bool $status, string $message): void
    {
        $index = count($this->copyResult);
        $this->copyResult[$index] = [
            "status" => $status,
            "message" => $message
        ];
    }

    /**
     * Process Programme field data
     *
     * @param Programme $programme programme object that would be updated
     * @param array $data array containing the data that would be passed to the programme object
     *
     * @return Programme
     */
    private function processProgramme(Programme $programme, $data) {
        if( isset($data['name']) ) {
            $programme->setName($data['name']);
        }
        if( isset($data['code']) ) {
            $programme->setCode($data['code']);
        }
        if( isset($data['welcome']) ) {
            if (strlen(trim((string) $data['welcome'])) > 0) {
                $welcome = trim((string) $data['welcome']);
                $this->cleanProgrammeData($welcome);
                $programme->setWelcome($welcome);
            }
        }
        if( isset($data['link_webmail']) ) {
            $programme->setLinkWebmail($data['link_webmail']);
        }
        if( isset($data['link_yammer']) ) {
            $programme->setLinkYammer($data['link_yammer']);
        }
        if( isset($data['link_myesuite']) ) {
            $programme->setLinkMyesuite($data['link_myesuite']);
        }
        if( isset($data['link_faculty_blog']) ) {
            $programme->setFacultyBlogs($data['link_faculty_blog']);
        }
        if( isset($data['link_knowledge']) ) {
            $programme->setesuiteKnowledge($data['link_knowledge']);
        }
        if( isset($data['link_amphihq']) ) {
            $programme->setLinkAmphiHq($data['link_amphihq']);
        }
        if( isset($data['link_learninghub']) ) {
            $programme->setLinkLearninghub($data['link_learninghub']);
        }
        if( isset($data['published']) ) {
            $programme->setPublished($data['published']);
        }
        if( isset($data['private']) ) {
            $programme->setPrivate($data['private']);
        }
        if( isset($data['starts_on_sunday']) ) {
            $programme->setStartsOnSunday($data['starts_on_sunday']);
        }
        if( isset($data['company_logo']) ) {
            $programme->setCompanyLogo($data['company_logo']);
        }
        if( isset($data['company_logo_size']) ) {
            $programme->setCompanyLogoSize($data['company_logo_size']);
        }
        if( isset($data['with_discussions']) ) {
            $programme->setWithDiscussions($data['with_discussions']);
        }
        if( isset($data['discussions_publish']) ) {
            $programme->setDiscussionsPublish($data['discussions_publish']);
        }
        if( isset($data['view_type']) ) {
            $programme->setViewType($data['view_type']);
        }
        if( isset($data['learning_journey']) ) {
            $programme->setLearningJourney($data['learning_journey']);
        }

        if( isset($data['learning_journey']) ) {
            $programme->setLearningJourney($data['learning_journey']);
        }

        return $programme;
    }


    private function getPublishedSubscribedCourses(Request $request,$programmeId)
    {
        $userId = $this->getCurrentUserId($request);
        $em     = $this->entityManager;
        $scope = $this->getCurrentUserScope($request);
        /** @var Query $query */

       if( $scope == 'edotssvc' || $scope=='edotsvc' ) {
           $query = $em->createQuery(
               'SELECT c FROM esuite\MIMBundle\Entity\Course c
            JOIN c.courseSubscriptions cs
            WHERE c.published = :published and c.programme = :programme_id'
           )
               ->setParameter('published', TRUE)
               ->setParameter('programme_id', $programmeId);

       }else {
           $query = $em->createQuery(
               'SELECT c FROM esuite\MIMBundle\Entity\Course c
            JOIN c.courseSubscriptions cs
            JOIN cs.user u
            WHERE c.published = :published and u.id = :user_id and c.programme = :programme_id'
           )
               ->setParameter('published', TRUE)
               ->setParameter('user_id', $userId)
               ->setParameter('programme_id', $programmeId);

       }


        return $query->getResult();
    }

    private function getSessionsWithPublishedHandouts(User $user)
    {
        $sessionsWithPublishedHandouts = [];

        if($user->getUserGroups()) {
            foreach($user->getUserGroups() as $userGroup) {
                /** @var GroupSession $groupSession */
                foreach($userGroup->getGroupSessions() as $groupSession) {
                    if($groupSession->getHandoutsPublished()) {
                        array_push($sessionsWithPublishedHandouts, $groupSession->getSession()->getId());
                    }
                }
            }
        }
        return $sessionsWithPublishedHandouts;
    }

    private function cleanProgrammeData(&$stringObject) {
        $stringObject = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $stringObject);
        $stringObject = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $stringObject);
        $stringObject = preg_replace('#<link(.*?)>(.*?)</link>#is', '', $stringObject);
        $stringObject = preg_replace('#<image(.*?)>#is', '', $stringObject);
        
        //removing inline js events
        $stringObject = preg_replace("/([ ]on[a-zA-Z0-9_-]{1,}=\".*\")|([ ]on[a-zA-Z0-9_-]{1,}='.*')|([ ]on[a-zA-Z0-9_-]{1,}=.*[.].*)/","", $stringObject);

        //removing inline js
        $stringObject = preg_replace("/([ ]href.*=\".*javascript:.*\")|([ ]href.*='.*javascript:.*')|([ ]href.*=.*javascript:.*)/i","", $stringObject);
    }

}
