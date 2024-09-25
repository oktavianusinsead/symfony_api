<?php

namespace esuite\MIMBundle\Controller;

use Exception;

use esuite\MIMBundle\Entity\Announcement;
use esuite\MIMBundle\Entity\CourseSubscription;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use esuite\MIMBundle\Exception\PermissionDeniedException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Exception\ConflictFoundException;
use esuite\MIMBundle\Exception\ForbiddenException;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Entity\UserAnnouncement;
use Doctrine\Persistence\ManagerRegistry;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserAnnouncementController extends BaseController
{

    #[Post("/profile/viewed-announcements/{announcementId}")]
    #[Allow(["scope" => "mimstudent,edotstudent"])]
    #[OA\Parameter(name: "announcementId", description: "AnnouncementId", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to mark an announcement as viewed.")]
    public function updateViewedAnnouncementAction(Request $request,$announcementId)
    {
        $this->setLogUuid($request);

        /** @var Announcement $announcement */
        $announcement = $this->findById('Announcement', $announcementId);

        // check if Announcement exist
        if (!$announcement) {
            $this->log("Invalid AnnouncementID: $announcementId");
            throw new ResourceNotFoundException();
        }

        $userAnnouncementData = new UserAnnouncement();
        $userAnnouncementData->setUser($this->getCurrentUserObj($request));
        $userAnnouncementData->setAnnouncement($announcement);
        $userAnnouncementData->setCourse($announcement->getCourse());

        // check if user is allowed to access this course announcement
        if (!$this->userHasCourseAccess($request,$announcement->getCourse()->getId())) {
            throw new ForbiddenException('User is not allowed to access this announcement');
        }

        try {
            $this->create('UserAnnouncement', $userAnnouncementData);
        } catch (Exception) {
            throw new ConflictFoundException('User announcement has been updated');
        }

        return $this->getUserAnnouncements($request);
    }

    #[Delete("/profile/viewed-announcements/{announcementId}")]
    #[Allow(["scope" => "mimstudent,edotstudent"])]
    #[OA\Parameter(name: "announcementId", description: "AnnouncementId", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to unmark a announcement as viewed.")]
    public function deleteViewedAnnouncementAction(Request $request,$announcementId)
    {
        $this->setLogUuid($request);

        // check if announcement is in the user announcements lists
        $criteria = array_filter(['user' => $this->getCurrentUserObj($request), 'announcement' => $this->findById('Announcement', $announcementId)]);

        $userAnnouncementCollection   = $this->findBy('UserAnnouncement', $criteria);
        /** @var UserAnnouncement $userAnnouncement */
        $userAnnouncement = $userAnnouncementCollection[0];
        $userAnnouncementId = $userAnnouncement->getId();
        return $this->deleteById('UserAnnouncement', $userAnnouncementId);
    }

    #[Get("/profile/viewed-announcements")]
    #[Allow(["scope" => "mimstudent,edotstudent"])]
    #[OA\Parameter(name: "request", description: "Expects GET parameter course to filter by course id", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get viewed Announcement.")]
    public function getViewedAnnouncementsAction(Request $request)
    {
        $this->setLogUuid($request);

        $courseId = $request->get('course');

        if ($courseId) {
            return $this->getUserAnnouncements($request,$courseId);
        }

        return $this->getUserAnnouncements($request);

    }

    #[Get("/profile/viewed-announcements")]
    #[Allow(["scope" => "mimstudent,edotstudent"])]
    #[OA\Parameter(name: "courseId", description: "Requesting Course Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Checks is a user has access to Course requested.")]
    private function userHasCourseAccess(Request $request,$courseId)
    {
        $courseUserRole = $this->doctrine->getRepository(CourseSubscription::class)->findBy(['user' => $this->getCurrentUserObj($request), 'course' => $courseId]);

        return count($courseUserRole) > 0;
    }

    #[OA\Parameter(name: "courseId", description: "Requesting Course Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Gets all User Announcement based on Current User.")]
    private function getUserAnnouncements(Request $request,$courseId = null)
    {

        try {
            if (is_null($courseId)) {
                $userAnnouncements = $this->doctrine->getRepository(UserAnnouncement::class)
                                         ->findBy(['user' => $this->getCurrentUserObj($request)]);
            } else {
                $userAnnouncements = $this->doctrine->getRepository(UserAnnouncement::class)
                                         ->findBy(['user' => $this->getCurrentUserObj($request), 'course' => $this->findById('Course', $courseId)]);
            }
        } catch (Exception) {
            $userAnnouncements = [];
        }

        $result = ['announcements' => []];

        foreach ($userAnnouncements as $userAnnouncement) {
            $result[ 'announcements' ][] = (int)$userAnnouncement->getAnnouncement()->getId();
        }

        return $result;
    }

}
