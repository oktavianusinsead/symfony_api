<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Exception\ForbiddenException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Redis\Base as Redis;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\CourseBackupManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Course")]
class CourseBackupController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, public CourseBackupManager $courseBackupManager, Redis $redis)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->courseBackupManager->loadServiceManager($redis);
    }

    #[Get("/courses/{courseId}/backup")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "courseId", description: "id of the course to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a backup file of a Course")]
    public function getCourseBackupAction(Request $request, $courseId)
    {
        return $this->courseBackupManager->getCourseBackupLink($request,$courseId);
    }

    #[Post("/courses/{courseId}/backup-email")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "courseId", description: "id of the course to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to register for email notification once Course Backup is ready")]
    public function emailNotifyCourseBackupAction(Request $request, $courseId)
    {
        return $this->courseBackupManager->notifyCourseBackup($request,$courseId);
    }

}
