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
use esuite\MIMBundle\Service\Manager\ProgrammeLearningJourneyManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Programme")]
class ProgrammeLearningJourneyController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, public ProgrammeLearningJourneyManager $programmeLearningJourneyManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $this->programmeLearningJourneyManager->loadServiceManager($s3, $baseParameterBag->get('edot.s3.config'));
    }

    #[Get("/programme-learning-journey/{programmeId}")]
    #[Allow(["scope" => "edotadmin,edotsuper,edotstudent"])]
    #[OA\Response(
        response: 200,
        description: "Get Learning Journey.")]
    public function getProgrammeLearningJourneyAction(Request $request, $programmeId)
    {
        $userId =  $this->getCurrentUserId($request);
        $scope = $this->getCurrentUserScope($request);

        return $this->programmeLearningJourneyManager->getProgrammeLearningJourney($request,$programmeId, $userId, $scope);
    }

    #[Post("/programme-learning-journey/{programmeId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Upload Learning Journey.")]
    public function updateProgrammeLearningJourneyAction(Request $request, $programmeId)
    {
        return $this->programmeLearningJourneyManager->updateProgrammeLearningJourney($request,$programmeId);
    }
}
