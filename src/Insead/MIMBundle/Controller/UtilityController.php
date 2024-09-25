<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Exception\BoxGenericException;
use Insead\MIMBundle\Exception\ForbiddenException;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Service\AIPService;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\CourseManager;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Manager\ProgrammeCompanyLogoManager;
use Insead\MIMBundle\Service\Manager\ProgrammeManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\Redis\Base as Redis;
use Insead\MIMBundle\Service\RestHTTPService;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\CalendarManager;
use Insead\MIMBundle\Service\Manager\CourseBackupManager;
use Insead\MIMBundle\Service\Manager\CoursePeopleManager;
use Insead\MIMBundle\Service\Manager\ProfileBookManager;
use Insead\MIMBundle\Service\Manager\SessionSheetManager;
use Insead\MIMBundle\Service\Manager\UtilityManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Utility")]
class UtilityController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                AuthToken $authToken,
                                private readonly LoginManager $login,
                                private readonly SessionSheetManager $sessionSheetManager,
                                private  readonly ProfileBookManager $profileBookManager,
                                ProgrammeManager $programmeManager,
                                private readonly CalendarManager $calendarManager,
                                private readonly CourseBackupManager $courseBackupManager,
                                Redis $redis,
                                ProgrammeCompanyLogoManager $programmeCompanyLogoManager,
                                CourseManager $courseManager,
                                RestHTTPService $restHTTPService,
                                ManagerBase $base)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $AIPService = new AIPService($logger, $baseParameterBag->get('aip.config'), $restHTTPService);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));

        $programmeManager->loadServiceManager($s3, $login, $baseParameterBag->get('acl.config'));
        $this->profileBookManager->loadServiceManager($s3, $this->login, $baseParameterBag->get('profilebook.config'));
        $this->calendarManager->loadServiceManager($s3, $login);
        $courseManager->loadServiceManager($profileBookManager, $sessionSheetManager, $calendarManager, $AIPService);
        $this->sessionSheetManager->loadServiceManager($s3, $login, $programmeManager, $programmeCompanyLogoManager, $courseManager);
        $this->courseBackupManager->loadServiceManager($redis);
    }

    /**
     * @throws OptimisticLockException
     * @throws BoxGenericException
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     * @throws ORMException
     */
    #[Post("/recyclePeople")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to recycle people from 1 course to another within the same programme.")]
    public function recyclePeopleAction(Request $request, CoursePeopleManager $coursePeopleManager, UtilityManager $utilityManager)
    {
        $utilityManager->loadServiceManager($coursePeopleManager, $this->baseParameterBag->get('utility.config'));
        $cleanedRequest = $utilityManager->recyclePeople($request);
        if (isset($cleanedRequest) && is_array($cleanedRequest)){
            if (count($cleanedRequest) > 0){
                foreach ($cleanedRequest as $copyPeople){
                    $people   = $copyPeople['people'];
                    $courseID = $copyPeople['courseID'];
                    $roleID   = $copyPeople['role'];

                    $this->log('Copying people from course '.$courseID.' with role id '.$roleID);
                    $coursePeopleManager->customAssignUserToCourse($request, $people, $courseID, $roleID);
                }
            } else {
                throw new InvalidResourceException("Invalid request 1");
            }
        } else {
            throw new InvalidResourceException("Invalid request 2");
        }

        return true;
    }

    #[Post("/imageToBase64")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to convert image to base64.")]
    public function imageToBase64Action(Request $request, UtilityManager $utilityManager, CoursePeopleManager $coursePeopleManager)
    {
        $utilityManager->loadServiceManager($coursePeopleManager, $this->baseParameterBag->get('utility.config'));
        return $utilityManager->imageToBase64($request);
    }

    /**
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     * @throws ORMException
     * @throws InvalidResourceException
     */
    #[Get("/programme-checklist/{programmeId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get status of the ff: 1. Profile book 2. Session sheet 3. Schedule 4. Back-up status (ZIP folder is downloadable) 5. Huddle discussion space only if it is enabled.")]
    public function programmeChecklistAction(Request $request, $programmeId)
    {
        /** @var Programme $programme */
        $programme = $this->doctrine
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if ($programme) {
            $programme->setOverriderReadonly(true);

            $courses = $programme->getPublishedCourses();

            $courseBackup = [];

            /** @var Course $course */
            foreach ($courses as $course) {

                try {
                    $backup = $this->courseBackupManager->getCourseBackupLink($request, $course->getId());
                    $backupStatus = true;

                } catch (ForbiddenException|ResourceNotFoundException $e) {
                    $backup = $e->getMessage();
                    $backupStatus= false;
                }

                $courseBackup[] = ['id' => $course->getId(), 'name' => $course->getName(), 'backup' => $backup, 'backup-status' => $backupStatus];
            }

            $huddle = ['programme' => $programme->getWithDiscussions(), 'is_published' => $programme->getDiscussionsPublish()];

            return ['profile-books' => $this->profileBookManager->getProfileBook($request, $programmeId), 'session-sheets' => $this->sessionSheetManager->getSessionSheet($request, $programmeId), 'calendars' => $this->calendarManager->getCalendar($request, $programmeId), 'backups' => $courseBackup, 'huddle' => $huddle];
        } else {
            throw new ResourceNotFoundException('Invalid programme');
        }
    }
}
