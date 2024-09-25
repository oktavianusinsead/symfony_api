<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\File\FileManager;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\edotNotify;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\TemplateSubtaskManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Subtask")]
class TemplateSubtaskController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                edotNotify $notify,
                                EntityManager $em,
                                public TemplateSubtaskManager $templateSubtaskManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $s3Object = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $fileManager = new FileManager($baseParameterBag->get('edot.s3.config'), $logger, $notify, $em, $s3Object, $base);
        $this->templateSubtaskManager->loadServiceManager($fileManager);
    }

    #[Get("/template-subtasks")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve Template Sub Tasks for admin. This API endpoint is restricted to coordinators only.")]
    public function getTemplateSubtasksAction(Request $request)
    {
        return $this->templateSubtaskManager->getTemplateSubtasks($request);
    }

    #[Get("/template-subtasks/{templateSubtaskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Template Sub Task. This API endpoint is restricted to coordinators only.")]
    public function getTemplateSubtaskAction(Request $request, $templateSubtaskId)
    {
        return $this->templateSubtaskManager->getTemplateSubtask($request,$templateSubtaskId);
    }

    #[Post("/template-subtasks")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to add a Sub Task.")]
    public function newTemplateSubtaskAction(Request $request)
    {
        return $this->templateSubtaskManager->newTemplateSubtask($request);
    }

    #[Post("/template-subtasks/{templateSubtaskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to update a Template Sub Task.")]
    public function updateTemplateSubtaskAction(Request $request, $templateSubtaskId)
    {
        return $this->templateSubtaskManager->updateTemplateSubtask($request,$templateSubtaskId);
    }

    #[Delete("/template-subtasks/{templateSubtaskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to update a Template Sub Task.")]
    public function deleteTemplateSubtaskAction(Request $request, $templateSubtaskId)
    {
        return $this->templateSubtaskManager->deleteTemplateSubtask($request,$templateSubtaskId);
    }
}
