<?php

namespace esuite\MIMBundle\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Nelmio\ApiDocBundle\Controller\DocumentationController;
use Nelmio\ApiDocBundle\Controller\SwaggerUiController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Doctrine\Common\Annotations\Reader;
use esuite\MIMBundle\Exception\PermissionDeniedException;
use esuite\MIMBundle\Annotations\Allow;

use Psr\Log\LoggerInterface;

class ScopeListener implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger, private readonly Reader $annotationsReader)
    {
    }

    #[ArrayShape([KernelEvents::CONTROLLER => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER  => ['onKernelController', 9998]];
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();
        if ($controller instanceof SwaggerUiController || $controller instanceof DocumentationController) return true;
        $controllerClass = $controller[0];
        $handlerMethod = $controller[1];

        $userScope = $event->getRequest()->getSession()->get('scope');
        $this->logger->debug('USER SCOPE::'.$userScope);
        $this->logger->debug('INSIDE SCOPE LISTENER!!!'.$controller[1]);

        $method =  new \ReflectionMethod($controllerClass, $handlerMethod);
        foreach ($this->annotationsReader->getMethodAnnotations($method) as $annotation) {
            $this->logger->debug('ANNOTATION FOUND::: '.$annotation::class);
            if ($annotation instanceof Allow) {
                $scope = $annotation->getScope();
                $this->logger->debug('SCOPE FOUND::'.count($scope));
                if(!in_array($userScope, $scope)) {
                    throw new PermissionDeniedException('You are not authorized to access this API Endpoint.');
                }
            }
        }
    }
}

