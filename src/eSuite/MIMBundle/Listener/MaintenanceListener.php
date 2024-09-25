<?php

namespace esuite\MIMBundle\Listener;

use esuite\MIMBundle\Service\Manager\MaintenanceManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class MaintenanceListener
{
    protected $maintenanceManager;
    protected $requestStack;

    public function __construct(MaintenanceManager $maintenanceManager, RequestStack $requestStack)
    {
        $this->maintenanceManager = $maintenanceManager;
        $this->requestStack       = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            if ($this->maintenanceManager->isMaintenance($request)) {
                $event->setResponse(
                    new Response(
                        'edot@esuite is in maintenance mode',
                        Response::HTTP_SERVICE_UNAVAILABLE
                    )
                );
                $event->stopPropagation();
            }
        }
    }

}
