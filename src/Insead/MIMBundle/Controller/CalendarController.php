<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\CalendarManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Calendar")]
class CalendarController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public CalendarManager $calendarManager,
                                AuthToken $authToken,
                                LoginManager $login)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $this->calendarManager->loadServiceManager($s3, $login);
    }

    #[Post("/calendars")]
    #[Allow(["scope" => "studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to plot course that matches the calendar criteria")]
    public function checkCalendarAction(Request $request)
    {
        return $this->calendarManager->extractCalendar( $request );
    }

    #[Get("/calendars/{programmeId}")]
    #[Allow(["scope" => "studysuper,studyadmin,studystudent"])]
    #[OA\Parameter(name: "request", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId", description: "Id of the programme to be generated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get a copy of the programme calendar")]
    public function getProgrammeCalendarLinksAction(Request $request, $programmeId)
    {
        return $this->calendarManager->getCalendar( $request, $programmeId );
    }

    #[Post("/calendars/{programmeId}")]
    #[Allow(["scope" => "studysuper,studyadmin"])]
    #[OA\Parameter(name: "request", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId", description: "Id of the programme to be generated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "function to initiate an update to the programme calendar")]
    public function generateProgrammeCalendarAction(Request $request, $programmeId)
    {
        return $this->calendarManager->generateProgrammeCalendar( $request, $programmeId );
    }

    #[Get("/calendars/{programmeId}/data")]
    #[Allow(["scope" => "studysuper,studyssvc,studysvc"])]
    #[OA\Parameter(name: "request", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId", description: "Id of the programme to be generated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to plot course that matches the calendar criteria")]
    public function getProgrammeCalendarInfoAction(Request $request, $programmeId)
    {
        return $this->calendarManager->getProgrammeCalendarInfo( $request, $programmeId );
    }
}
