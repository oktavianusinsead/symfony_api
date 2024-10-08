<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Entity\Activity;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Service\AIPService;
use esuite\MIMBundle\Service\Barco\User;
use esuite\MIMBundle\Service\Barco\UserGroups;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\UserCheckerManager;
use esuite\MIMBundle\Service\Manager\UserProfileManager;
use esuite\MIMBundle\Service\Manager\UtilityManager;
use esuite\MIMBundle\Service\RestHTTPService;
use Nelmio\ApiDocBundle\Annotation\Model;
use PhpOffice\PhpSpreadsheet\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\BarcoManager;

use OpenApi\Attributes as OA;

#[OA\Tag(name: "Barco")]
class BarcoUserGroupController extends BarcoUserBaseController
{

    #[Get("/barco-user-groups")]
    #[Allow(["scope" => "edotadmin, edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to get list of user group")]
    public function getBarcoUserGroupListsAction(Request $request)
    {
        return $this->barcoManager->getUserGroupList($request);
    }

    #[Post("/barco-batch-enroll")]
    #[Allow(["scope" => "edotadmin, edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to get list of user group")]
    public function barcoBatchEnrollAction(Request $request)
    {
        return $this->barcoManager->barcoBatchEnroll($request);
    }

    #[Post("/barco-manual-enroll-user/{groupId}")]
    #[Allow(["scope" => "edotadmin, edotsuper"])]
    #[OA\Parameter(name: "groupId", description: "Group ID", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to enroll a user in barco")]
    public function barcoManualEnrollAction(Request $request, $groupId)
    {
        $peopleSoftIDoresuiteEMail = $request->get('peopleSoftIDorEmailToAdd');
        return $this->barcoManager->barcoManualEnroll($groupId, $peopleSoftIDoresuiteEMail);
    }

    #[Delete("/barco-user-groups/{barcogroupId}/users/{barcoUserId}")]
    #[Allow(["scope" => "edotadmin, edotsuper"])]
    #[OA\Parameter(name: "barcogroupId", description: "Barco Group ID", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "barcoUserId", description: "Barco User ID", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "function to remove user from group")]
    public function removeBarcoUserFromGroupAction($barcogroupId, $barcoUserId)
    {
        return $this->barcoManager->removeUserFromList($barcogroupId, $barcoUserId);
    }


    #[Post("/barco-user-groups/{barcoGroupId}/users")]
    #[Allow(["scope" => "edotadmin, edotsuper"])]
    #[OA\Parameter(name: "barcoGroupId", description: "Barco Group ID", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to add users to group (batch upload)")]
    public function addBarcoUsersToGroupAction(Request $request, $barcoGroupId)
    {
        return $this->barcoManager->addUserToGroup($request, $barcoGroupId);
    }

    #[Post("/barco-user-groups/new")]
    #[Allow(["scope" => "edotadmin, edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to add new userGroup")]
    public function addNewBarcoUserGroupAction(Request $request)
    {
        return $this->barcoManager->addNewUserGroup($request);
    }


    #[Post("/barco-user-groups/add-to-db")]
    #[Allow(["scope" => "edotadmin, edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to add/update user group details to DB. This could happen if new group is created manually or the group is in different ENV in edot")]
    public function addBarcoUserGroupToDBAction(Request $request)
    {
        return $this->barcoManager->addUserGroupToDB($request);
    }

    #[Delete("/barco-user-groups/{barcoGroupId}/delete")]
    #[Allow(["scope" => "edotadmin, edotsuper"])]
    #[OA\Response(
        response: 200,
        description: " Handler function to delete user group. This could happen if new group is created manually or the group is in different ENV in edot")]
    public function deleteBarcoUserGroupAction($barcoGroupId)
    {
        return $this->barcoManager->deleteBarcoUserGroup($barcoGroupId);
    }

}
