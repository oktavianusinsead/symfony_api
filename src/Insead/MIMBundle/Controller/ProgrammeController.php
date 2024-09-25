<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Manager\ProgrammeManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use Insead\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Nelmio\ApiDocBundle\Annotation\Model;

use Insead\MIMBundle\Entity\Programme;

#[OA\Tag(name: "Programme")]
class ProgrammeController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public ProgrammeManager $programmeManager,
                                AuthToken $authToken,
                                LoginManager $login)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $this->programmeManager->loadServiceManager($s3, $login, $baseParameterBag->get('acl.config'));
    }

    #[Put("/programmes")]
    #[Allow(["scope" => "studyadmin, studysuper"])]
    #[Security(name:"Bearer")]
    #[OA\Parameter(name: "name", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "code", description: "Code of the programme", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "welcome", description: "Welcome note for the programme", in: "query", schema: new OA\Schema(type:"string"))]
    #[OA\Parameter(name: "link_webmail", description: "For WebMail of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "link_yammer", description: "Name of the programme", in: "query",  schema: new OA\Schema(type: "boolean", default:true, enum: [true, false]))]
    #[OA\Parameter(name: "link_myinsead", description: "Description of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "link_faculty_blog", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "link_knowledge", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "published", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "private", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "starts_on_sunday", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "company_logo", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "with_discussions", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "boolean", default: true, enum: [true, false]))]
    #[OA\Parameter(name: "view_type", description: "Name of the programme", in: "query", schema: new OA\Schema(type: "int", default: 3))]
    #[OA\Response(
         response: 200,
         description: "Handler function to create a new Programme. This API endpoint is restricted to coordinators only", 
         content: new Model(type: Programme::class))]
    public function createProgrammeAction(Request $request)
    {
        return $this->programmeManager->createProgramme($request);
    }

    #[Get("/programmes/{programmeId}")]
    #[Allow(["scope" => "mimstudent,studystudent,studyadmin,studysuper,studyssvc,studysvc"])]
    #[Security(name:"Bearer")]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Programme",
        content: new Model(type: Programme::class))]
    public function getProgrammeAction(Request $request, $programmeId)
    {
        return $this->programmeManager->getProgramme($request,$programmeId);
    }


    #[Get("/programmes")]
    #[Allow(["scope" => "mimstudent,studystudent,studyadmin,studysuper"])]
    #[OA\Response(
         response: 200,
         description: "Handler function to retrieve all Programmes base from token")]
    public function listProgrammesAction(Request $request)
    {
        return $this->programmeManager->getProgrammes($request);
    }

    #[Post("/programmes/{programmeId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
          response: 200,
          description: "Handler function to Update a Programme. This API endpoint is restricted to coordinators only",
          content: new Model(type: Programme::class))]
    public function updateProgrammeAction(Request $request, $programmeId)
    {
        return $this->programmeManager->updateProgramme($request,$programmeId);
    }

    #[Delete("/programmes/{programmeId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
           response: 200,
           description: "Handler function to Delete a Programme. This API endpoint is restricted to coordinators only")]
    public function deleteProgrammeAction(Request $request, $programmeId)
    {
        return $this->programmeManager->deleteProgramme($request,$programmeId);
    }

    #[Post("/programmes/{programmeId}/coordinators")]
    #[Allow(["scope" => "studysuper"])]
    #[OA\Response(
            response: 200,
            description: "Handler function to list assigned users to a Programme. This API endpoint is restricted to superuser only",
            content: new Model(type: Programme::class))]
    public function updateCoordinatorsAction(Request $request, $programmeId)
    {
        return $this->programmeManager->updateCoordinatorsFromProgramme($request,$programmeId);
    }

    #[Post("/programmes/{programmeId}/copy")]
    #[Allow(["scope" => "studysuper,studyadmin"])]
    #[OA\Response(
             response: 200,
             description: "Handler function to copy a Programme. This API endpoint is restricted to coordinators only")]
    public function copyProgrammeAction(Request $request, $programmeId)
    {
        return $this->programmeManager->copyProgramme($request,$programmeId);
    }

    #[Post("/copy-programme/initiate")]
    #[OA\Response(
              response: 200,
              description: "Handler to process the copy programme")]
    public function initiateCopyProgrammeAction(Request $request) {
        return $this->programmeManager->initiateCopyProgramme($request);
    }
}
