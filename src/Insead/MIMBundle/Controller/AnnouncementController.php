<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Redis\AdminAnnouncement as Redis;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\AnnouncementManager;
use OpenApi\Attributes as OA;

#[OA\Tag("Announcement")]
class AnnouncementController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, public AnnouncementManager $announcementManager, Redis $redis)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->announcementManager->loadServiceManager($redis, $baseParameterBag->get('acl.config'));
    }
    
    #[Put("/announcements")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a new Announcement. This API endpoint is restricted to coordinators only")]
    public function createAnnouncementAction(Request $request)
    {
        return $this->announcementManager->createAnnouncement($request);
    }

    #[Post("/announcements/{announcementId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update an Announcement. This API endpoint is restricted to coordinators only")]
    public function updateAnnouncementAction(Request $request, $announcementId)
    {
        return $this->announcementManager->updateAnnouncement($request,$announcementId);
    }

    #[Get("/announcements/{announcementId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve an Announcement. This API endpoint is restricted to coordinators only")]
    public function getAnnouncementAction(Request $request, $announcementId)
    {
        return $this->announcementManager->getAnnouncement($request,$announcementId);
    }

    #[Get("/announcements")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Announcements. This API endpoint is restricted to coordinators only")]
    public function getAnnouncementsAction(Request $request)
    {
        return $this->announcementManager->getAnnouncements($request);
    }

    #[Delete("/announcements/{announcementId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete an Announcement. This API endpoint is restricted to coordinators only")]
    public function deleteAnnouncementAction(Request $request, $announcementId)
    {
        return $this->announcementManager->deleteAnnouncement($request,$announcementId);
    }


    #[Get("/admin/announcements")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Announcements. This API endpoint is restricted to coordinators only")]
    public function getAdminAnnouncementAction(Request $request)
    {
        return $this->announcementManager->getAdminAnnouncement($request);
    }

    #[Post("/admin/announcements")]
    #[Allow(["scope" => "studysuper"])]
    #[OA\Response(
        response: 200,
        description: "save function to admin  Announcement. This API endpoint is restricted to coordinators only")]
    public function updateAdminAnnouncementAction(Request $request)
    {
        return $this->announcementManager->updateAdminAnnouncement($request);
    }
}
