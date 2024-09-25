<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Service\Manager\Base;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\LearningJourneyManager;
use Symfony\Component\HttpFoundation\Tests\JsonSerializableObject;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Learning Journey")]
class LearningJourneyController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, public LearningJourneyManager $learningJourneyManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $this->learningJourneyManager->loadServiceManager($s3, $baseParameterBag->get('study.s3.config'));
    }

    #[Post("/learning-journey/{programmeId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update an LearningJourney. This API endpoint is restricted to coordinators only.")]
    public function updateLearningJourneyAction(Request $request, $programmeId)
    {
        return $this->learningJourneyManager->updateLearningJourney($request,$programmeId);
    }

    #[Get("/learning-journey/{programmeId}")]
    #[Allow(["scope" => "mimstudent,studystudent,studyadmin,studysuper,studyssvc,studysvc"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve an LearningJourney.")]
    public function getLearningJouneyAction(Request $request, $programmeId)
    {
        $userId =  $this->getCurrentUserId($request);
        $scope = $this->getCurrentUserScope($request);
        return $this->learningJourneyManager->getLearningJourney($request,$programmeId, $userId, $scope);
    }   
}
