<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Insead\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Insead\MIMBundle\Service\Manager\ProgrammeManager;

#[OA\Tag(name: "Programme")]
class ProgrammeUtilityController extends BaseController
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
    
   #[Post("/programmes/archive/{programmeId}")]
   #[Allow(["scope" => "studyadmin,studysuper"])]
   #[OA\Response(
        response: 200,
        description: "Handler function to archive a Programme",
        content: new Model(type: Programme::class))]
    public function archiveProgrammeAction(Request $request, $programmeId)
    {
        return $this->programmeManager->archiveProgramme($request,$programmeId);
    }

   #[Get("/programmes/archives")]
   #[Allow(["scope" => "studyadmin,studysuper"])]
   #[OA\Response(
         response: 200,
         description: "Handler function to list archive Programmes",
         content: new Model(type: Programme::class))]
    public function archiveProgrammeListAction(Request $request)
    {
        return $this->programmeManager->archiveProgrammeList($request);
    }

   #[Get("/programmes/{programmeId}/courses")]
   #[Allow(["scope" => "mimstudent,studystudent"])]
   #[OA\Response(
          response: 200,
          description: "Handler function to list courses belonging to a Programme",
          content: new Model(type: Programme::class))]
    public function listBelongingCoursesAction(Request $request, $programmeId)
    {
        return $this->programmeManager->getCoursesFromProgramme($request,$programmeId);
    }


   #[Get("/programmes/{programmeId}/people")]
   #[Allow(["scope" => "mimstudent,studystudent,studyadmin,studysuper"])]
   #[OA\Response(
       response: 200,
       description: "Handler function to list assigned users to a Programme",
       content: new Model(type: Programme::class))]
    public function listAssignedPeopleAction(Request $request, $programmeId)
    {
        return $this->programmeManager->getUsersFromProgramme($request,$programmeId);
    }


    #[Get("/programmes/{programmeId}/coordinators")]
    #[Allow(["scope" => "studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to list assigned users to a Programme",
        content: new Model(type: Programme::class))]
    public function listCoordinatorsAction(Request $request, $programmeId)
    {
        return $this->programmeManager->getCoordinatorsFromProgramme($request,$programmeId);
    }
}
