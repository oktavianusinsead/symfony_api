<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\CalendarManager;
use esuite\MIMBundle\Service\Manager\LoginManager;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\SessionManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Session")]
class SessionController extends BaseController
{
    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Session";

    public function __construct(LoggerInterface                  $logger,
                                ManagerRegistry                  $doctrine,
                                ParameterBagInterface            $baseParameterBag,
                                private readonly SessionManager  $sessionManager,
                                private readonly CalendarManager $calendarManager,
                                AuthToken $authToken,
                                LoginManager                     $login,
                                ManagerBase $base)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $this->calendarManager->loadServiceManager($s3, $login);
        $this->sessionManager->loadServiceManager($this->calendarManager);
    }

    #[Put("/sessions")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a new Sessions. This API endpoint is restricted to coordinators only")]
    public function createSessionAction(Request $request, SessionManager $sessionManager)
    {
        $this->setLogUuid($request);

        $paramList = "name,remarks,description,position,course_id,abbreviation,slot_start,slot_end,published,is_scheduled,alternate_session_name,session_color";

        $data = $this->loadDataFromRequest( $request, $paramList );
        $data[ "logUuid" ] = $this->logUuid;

        return $this->sessionManager->createSession($request, $data);
    }

    #[Post("/sessions/{sessionId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Session. This API endpoint is restricted to coordinators only")]
    public function updateSessionAction(Request $request, $sessionId)
    {
        return $this->sessionManager->updateSession($request,$sessionId);
    }

    #[Get("/sessions/{sessionId}")]
    #[Allow(["scope" => "edotadmin,edotsuper,edotssvc,edotsvc,edotstudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Session.")]
    public function getSessionAction(Request $request, $sessionId)
    {
        return $this->sessionManager->getSession($request,$sessionId);
    }

    #[Get("/sessions")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Session.")]
    public function getSessionsAction(Request $request)
    {
        $this->setLogUuid($request);

        $sessions = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("SESSION: ".$id);
            $session = $this->findById(self::$ENTITY_NAME, $id);
            $sessions[] = $session->serializeOnlyPublished(TRUE);
        }
        return ['sessions' => $sessions];
    }

    #[Delete("/sessions/{sessionId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Session.")]
    public function deleteSessionAction(Request $request, $sessionId)
    {
        return $this->sessionManager->deleteSession($request,$sessionId);
    }

    #[Post("/sessions/{sessionId}/people")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to assign a Professor to a Session.")]
    public function assignProfessorToSessionAction(Request $request, $sessionId)
    {
        return $this->sessionManager->assignProfessorToSession($request,$sessionId);
    }

    #[Delete("/sessions/{sessionId}/people/{peoplesoftId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to unassign a Professor to a Session.")]
    public function unAssignProfessorToSessionAction(Request $request, $sessionId, $peoplesoftId)
    {
        return $this->sessionManager->unAssignProfessorToSession($request,$sessionId,$peoplesoftId);
    }

}
