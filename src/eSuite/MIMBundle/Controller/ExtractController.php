<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\ExtractManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Diagnostics")]
class ExtractController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, private readonly ExtractManager $extractManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->extractManager->loadServiceManager($baseParameterBag->get('edot.backup.config'));
    }

    #[Get("/extract/programmes")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "API Endpoint URL", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all Programmes")]
    public function getAllProgrammesAction(Request $request)
    {
        return $this->extractManager->extractAllProgramme( $request );
    }

    #[Get("/extract/courses")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "API Endpoint URL", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all Courses")]
    public function getAllCoursesAction(Request $request)
    {
        return $this->extractManager->extractAllCourse( $request );
    }

    #[Get("/extract/sessions")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all Sessions")]
    public function getAllSessionsAction(Request $request)
    {
        return $this->extractManager->extractAllSession( $request );
    }

    #[Get("/extract/users")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all users")]
    public function getAllUsersAction(Request $request)
    {
        return $this->extractManager->extractAllUser( $request );
    }

    #[Get("/extract/admins")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all administrators")]
    public function getAllAdminsAction(Request $request)
    {
        return $this->extractManager->extractAllAdmin( $request );
    }

    #[Get("/extract/non-participants")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all users that have non participant roles")]
    public function getNonParticipantsAction(Request $request)
    {
        return $this->extractManager->extractAllNonParticipant( $request );
    }

    #[Get("/extract/composer")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to composer.lock file")]
    public function getComposerLockAction(Request $request)
    {
        return $this->extractManager->extractComposerLock( $request );
    }

    #[Get("/extract/profile-cache/{peoplesoftId}")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peoplesoftId ", description: "peoplesoftId of user to extract", in: "query", schema: new OA\Schema(type: "String"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all users that have non participant roles")]
    public function getProfileCacheAction(Request $request, $peoplesoftId)
    {
         return $this->extractManager->extractProfileCache($request, $peoplesoftId);
    }

    #[Get("/extract/organization/{extOrgId}")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request ", description: "Request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "extOrgId ", description: "peoplesoftId of user to extract", in: "query", schema: new OA\Schema(type: "String"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all organization details")]
    public function getOrganizationAction(Request $request, $extOrgId)
    {
        return $this->extractManager->extractOrganization($request, $extOrgId);
    }

    #[Get("/extract/all")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all organization details")]
    public function getCustomAllAction(Request $request)
    {
        return $this->extractManager->extractCustomAll($request);
    }

}
