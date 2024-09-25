<?php
/**
 * Created by PhpStorm.
 * User: jeffersonmartin
 * Date: 12/11/18
 * Time: 11:22 AM
 */

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Service\AIPService;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\CalendarManager;
use Insead\MIMBundle\Service\Manager\CourseManager;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Manager\ProfileBookManager;
use Insead\MIMBundle\Service\Manager\ProgrammeCompanyLogoManager;
use Insead\MIMBundle\Service\Manager\ProgrammeManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\RestHTTPService;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\SessionSheetManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Session Sheet")]
class SessionSheetController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                private readonly SessionSheetManager $sessionSheetManager,
                                AuthToken $authToken,
                                LoginManager $login,
                                private readonly CourseManager $courseManager,
                                ProgrammeManager $programmeManager,
                                ProgrammeCompanyLogoManager $programmeCompanyLogoManager,
                                ProfileBookManager $profileBookManager,
                                CalendarManager $calendarManager,
                                RestHTTPService $restHTTPService)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $AIPService = new AIPService($logger, $baseParameterBag->get('aip.config'), $restHTTPService);
        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $programmeManager->loadServiceManager($s3, $login, $baseParameterBag->get('acl.config'));
        $programmeCompanyLogoManager->loadServiceManager($s3, $baseParameterBag->get('study.s3.config'));
        $profileBookManager->loadServiceManager($s3, $login, $baseParameterBag->get('profilebook.config'));
        $calendarManager->loadServiceManager($s3, $login);

        $this->courseManager->loadServiceManager($profileBookManager, $this->sessionSheetManager, $calendarManager, $AIPService);
        $this->sessionSheetManager->loadServiceManager($s3, $login, $programmeManager, $programmeCompanyLogoManager, $this->courseManager);
    }

    #[Post("/session-sheets/{programmeId}")]
    #[Allow(["scope" => "studyssvc,studysvc,studyadmin,studysuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId ", description: "Id of the Programme to pull the sessions", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all published sessions in a Programme")]
    public function generateSessionSheetAction(Request $request, $programmeId)
    {
        return $this->sessionSheetManager->getAllPublishedSessions( $request, $programmeId );
    }

    #[Get("/session-sheets/{programmeId}")]
    #[Allow(["scope" => "studyssvc,studystudent,studysvc,studyadmin,studysuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId ", description: "Id of the Programme to pull the sessions", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get url to download the session sheet from S3")]
    public function getSessionSheetAction(Request $request, $programmeId)
    {
        return $this->sessionSheetManager->getSessionSheet( $request, $programmeId );
    }

    #[Post("/session-sheets/{programmeId}/generate")]
    #[Allow(["scope" => "studyssvc,studysvc,studyadmin,studysuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId ", description: "Id of the Programme to pull the sessions", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to generate session sheet PDF document")]
    public function generateSessionSheetPDFAction(Request $request, $programmeId)
    {
        return $this->sessionSheetManager->generateSessionSheetPDF( $request, $programmeId );
    }
}
