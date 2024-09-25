<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Exception\InvalidResourceException;
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
use FOS\RestBundle\Controller\Annotations\Options;
use FOS\RestBundle\Controller\Annotations\Put;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\TemplateTaskManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Task")]
class TemplateTaskController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                edotNotify $notify,
                                EntityManager $em,
                                public TemplateTaskManager $templateTaskManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->base = $base;

        $s3Object = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $fileManager = new FileManager($baseParameterBag->get('edot.s3.config'), $logger, $notify, $em, $s3Object, $base);
        $this->templateTaskManager->loadServiceManager($fileManager);
    }

    #[Get("/template-tasks")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve Template Tasks for admin. This API endpoint is restricted to coordinators only.")]
    public function getTemplateTasksAction(Request $request, TemplateTaskManager $templateTaskManager)
    {
        return $templateTaskManager->getTemplateTasks($request);
    }

    #[Get("/template-tasks/{templateTaskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Template Task. This API endpoint is restricted to coordinators only.")]
    public function getTemplateTaskAction(Request $request, $templateTaskId, TemplateTaskManager $templateTaskManager)
    {
        return $this->templateTaskManager->getTemplateTask($request,$templateTaskId);
    }

    #[Post("/template-tasks/{taskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Copy a Task as a template. This API endpoint is restricted to coordinators only.")]
    public function createTemplateTaskAction(Request $request, $taskId, TemplateTaskManager $templateTaskManager)
    {
        return $this->templateTaskManager->copyTaskAsTemplate($request,$taskId);
    }

    #[Post("/template-tasks/{templateTaskId}/create")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a Task from a template. This API endpoint is restricted to coordinators only.")]
    public function createTaskFromTemplateAction(Request $request, $templateTaskId, TemplateTaskManager $templateTaskManager)
    {
        return $this->templateTaskManager->createFromTemplate($request,$templateTaskId);
    }

    #[Post("/template-tasks/{templateTaskId}/update")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a template task. This API endpoint is restricted to coordinators only.")]
    public function updateTemplateTaskAction(Request $request, $templateTaskId, TemplateTaskManager $templateTaskManager)
    {
        return $this->templateTaskManager->updateTemplateTask($request,$templateTaskId);
    }

    #[Delete("/template-tasks/{templateTaskId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete template task. This API endpoint is restricted to coordinators only.")]
    public function deleteTemplateTaskAction(Request $request, $templateTaskId, TemplateTaskManager $templateTaskManager)
    {
        return $this->templateTaskManager->deleteTemplateTask($request,$templateTaskId);
    }

    #[Put("/template-tasks")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create new Template task. This API endpoint is restricted to coordinators only.")]
    public function newTemplateTaskAction(Request $request, TemplateTaskManager $templateTaskManager)
    {
        return $this->templateTaskManager->newTemplateTask($request);
    }
}
