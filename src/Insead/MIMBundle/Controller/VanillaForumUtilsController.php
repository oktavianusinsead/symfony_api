<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Delete ;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Vanilla Forums")]
class VanillaForumUtilsController extends VanillaForumBaseController
{

    #[Get("/discussion/{programmeId}/users/listByRole")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to check the status of vanilla forum.")]
    public function getForumUserListByRoleAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->getUsersListByRole($request, $programmeId);
    }

    #[Get("/discussion/{programmeId}/status")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to check the status of vanilla forum.")]
    public function getForumStatusAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->getForumStatus($request, $programmeId);
    }

    #[Get("/discussion/{programmeId}/group/list")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to list all vanilla group under a programme.")]
    public function getForumGroupListAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->getForumGroupList($request, $programmeId);
    }

    #[Get("/discussion/{programmeId}/discussion/list")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to list discussion under a programme.")]
    public function forumDiscussionListAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->listDiscussion($request, $programmeId);
    }

    #[Get("/discussion/{programmeId}/url")]
    #[Allow(["scope" => "mimstudent,studystudent,studyadmin,studysuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function for fetching vanilla URL to be used by web and iOS.")]
    public function getVanillaURLAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->getURL($request, $programmeId);
    }

    /**
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/discussion/{programmeId}/discussion/{discussionId}/reopen")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "discussionId ", description: "discussion ID", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update a vanilla discussion.")]
    public function reOpenDiscussionAction(Request $request, $programmeId, $discussionId)
    {
        return $this->vanillaForumManager->reOpenDiscussion($request, $programmeId, $discussionId);
    }

    #[Get("/discussion/{programmeId}/peopleListWithRole")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "discussionId ", description: "discussion ID", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update the status of the user isAdded to yes (Rest client only).")]
    public function getPeopleListWIthRoleAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->peopleListWIthRole($request, $programmeId);
    }

    #[Get("/discussion/pendingUser/list")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to get pending users list (Rest client only).")]
    public function getPendingUserListAction(Request $request)
    {
        return $this->vanillaForumManager->listOfPendingUser($request);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/discussion/pendingUser/updateIsAdded")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to update the status of the user isAdded to yes (Rest client only).")]
    public function updateVanillaUserIsAddedAction(Request $request)
    {
        return $this->vanillaForumManager->updateVanillaUserIsAdded($request);
    }
}
