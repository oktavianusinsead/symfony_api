<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\ProgrammeCompanyLogoManager;
use esuite\MIMBundle\Service\Manager\Base;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Programme")]
class ProgrammeCompanyLogoController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, public ProgrammeCompanyLogoManager $programmeCompanyLogoManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $this->programmeCompanyLogoManager->loadServiceManager($s3, $baseParameterBag->get('edot.s3.config'));
    }

    #[Get("/programme-logo/{programmeId}")]
    #[Allow(["scope" => "edotadmin,edotsuper,edotstudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve the list of possible Programme Core Group. This API endpoint is restricted to coordinators only.")]
    public function getProgrammeCompanyLogoAction(Request $request, $programmeId)
    {
        $userId =  $this->getCurrentUserId($request);
        $scope = $this->getCurrentUserScope($request);

        return $this->programmeCompanyLogoManager->getProgrammeCompanyLogo($request,$programmeId, $userId, $scope);
    }

    #[Post("/programme-logo/{programmeId}")]
    #[Allow(["scope" => "edotadmin,edotsupert"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update Programme Core Group. This API endpoint is restricted to coordinators only.")]
    public function updateProgrammeCompanyLogoAction(Request $request, $programmeId)
    {
        return $this->programmeCompanyLogoManager->updateProgrammeCompanyLogo($request,$programmeId);
    }
}
