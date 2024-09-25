<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Delete ;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Vanilla Forums")]
class VanillaForumAdministrationController extends VanillaForumBaseController
{

    /**
     * @throws InvalidResourceException
     */
    #[Post("/discussion/{programmeId}/group/create")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to create a vanilla group.")]
    public function createForumGroupAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->createForumGroup($request, $programmeId);
    }

    /**
     * @throws InvalidResourceException
     */
    #[Delete("/discussion/{programmeId}/group/{groupId}/delete")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "groupId ", description: "vanilla group id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to delete a vanilla group.")]
    public function deleteForumGroupAction(Request $request, $programmeId, $groupId)
    {
        return $this->vanillaForumManager->deleteForumGroup($request, $programmeId, $groupId);
    }

    /**
     * @return array
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/discussion/{programmeId}/group/{groupId}/member/{vanillaUserId}/add")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "groupId ", description: "vanilla group id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "vanillaUserId ", description: "vanilla user id of the edot User", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to add member to a group.")]
    public function addMemberGroupAction(Request $request, $programmeId, $groupId, $vanillaUserId)
    {
        return $this->vanillaForumManager->addMemberToGroupQueue($request, $programmeId, $groupId, $vanillaUserId);
    }

    /**
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Delete("/discussion/{programmeId}/group/{groupId}/member/{vanillaUserId}/remove")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "groupId ", description: "vanilla group id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "vanillaUserId ", description: "vanilla user id of the edot User", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to remove a  member from a group.")]
    public function removeMemberGroupAction(Request $request, $programmeId, $groupId, $vanillaUserId)
    {
        return $this->vanillaForumManager->removeMemberFromGroupQueue($request, $programmeId, $groupId, $vanillaUserId);
    }

    /**
     * @throws InvalidResourceException
     */
    #[Post("/discussion/{programmeId}/discussion/create")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to create a vanilla discussion.")]
    public function createDiscussionAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->createDiscussion($request, $programmeId);
    }

    /**
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Delete("/discussion/{programmeId}/discussion/{discussionId}/remove")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "discussionId ", description: "discussion ID", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to delete a vanilla discussion.")]
    public function removeDiscussionAction(Request $request, $programmeId, $discussionId)
    {
        return $this->vanillaForumManager->removeDiscussion($request, $programmeId, $discussionId);
    }

    /**
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/discussion/{programmeId}/discussion/{discussionId}/close")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "discussionId ", description: "discussion ID", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to close a vanilla discussion.")]
    public function closeDiscussionAction(Request $request, $programmeId, $discussionId)
    {
        return $this->vanillaForumManager->closeDiscussion($request, $programmeId, $discussionId);
    }

    /**
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/discussion/{programmeId}/discussion/{discussionId}/update")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "discussionId ", description: "discussion ID", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update a vanilla discussion.")]
    public function updateDiscussionAction(Request $request, $programmeId, $discussionId)
    {
        return $this->vanillaForumManager->updateDiscussion($request, $programmeId, $discussionId);
    }

    /**
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/discussion/{programmeId}/group/{groupId}/update")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Parameter(name: "groupId ", description: "Group ID", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to update a vanilla group.")]
    public function updateGroupAction(Request $request, $programmeId, $groupId)
    {
        return $this->vanillaForumManager->updateGroup($request, $programmeId, $groupId);
    }

    /**
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/discussion/{programmeId}/conversation/create")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "programmeId ", description: "Programme Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to create conversation for user.")]
    public function conversationCreateAction(Request $request, $programmeId)
    {
        return $this->vanillaForumManager->createConversation($request, $programmeId);
    }

}
