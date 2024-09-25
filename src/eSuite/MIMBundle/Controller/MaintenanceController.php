<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\Redis\Maintenance;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\MaintenanceManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Maintenance")]
class MaintenanceController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, public MaintenanceManager $maintenanceManager, Maintenance $redisMaintenance, AuthToken $redisAuthToken)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->maintenanceManager->loadServiceManager($redisMaintenance, $redisAuthToken);
    }

    #[Get("/showMaintenance")]
    public function showMaintenanceAction(){
        $maintenance = $this->maintenanceManager->getMaintenance(new Request());
        return new Response($this->renderView(
            '@MIM/maintenance.html.twig',
            ['msg' => $maintenance['maintenances']['message']]
        ),200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Get("/maintenances")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to get Maintenance details. This API endpoint is restricted to edot SUPER only.")]
    public function getMaintenanceAction(Request $request)
    {
        return $this->maintenanceManager->getMaintenance($request);
    }

    #[Post("/maintenances/{id}")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to update Maintenance details. This API endpoint is restricted to edot SUPER only.")]
    public function updateMaintenanceAction(Request $request)
    {
        return $this->maintenanceManager->updateMaintenance($request);
    }

    #[Post("/forceMaintenanceOff")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to force turn off Maintenance.")]
    public function forceTurnOffMaintenanceAction(Request $request)
    {
        return $this->maintenanceManager->forceTurnOffMaintenance($request);
    }
}
