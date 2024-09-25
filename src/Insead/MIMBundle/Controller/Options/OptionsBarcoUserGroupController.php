<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsBarcoUserGroupController extends BaseController
{
    /**
     *  Handler function to get list of user group
     */
    #[Options("/barco-user-groups")]
    public function optionsBarcoUserGroupListsAction(){}

    /**
     *  Handler function to enroll user in group by batch
     */
    #[Options("/barco-batch-enroll")]
    public function optionsBarcoBatchEnrollAction(Request $request){}

    /**
     *  Handler function to enroll a user in barco
     *
     * @param Request $request
     * @param string $groupId
     */
    #[Options("/barco-manual-enroll-user/{groupId}")]
    public function optionsBarcoManualEnrollAction($request, $groupId){}

    /**
     *  Handler function to get list of user group
     *
     * @param $barcogroupId
     * @param $barcoUserId
     *
     */
    #[Options("/barco-user-groups/{barcogroupId}/users/{barcoUserId}")]
    public function optionsRemoveBarcoUserFromGroupAction($barcogroupId, $barcoUserId){}

    /**
     *  Handler function to add users to group
     *
     * @param string $barcoGroupId
     */
    #[Options("/barco-user-groups/{barcogroupId}/users")]
    public function optionsAddBarcoUsersToGroupAction(Request $request, $barcoGroupId){}

    /**
     *  Handler function to add new userGroup
     *
     * @return mixed
     */
    #[Options("/barco-user-groups/new")]
    public function optionsAddNewBarcoUserGroupAction(Request $request){}

    /**
     * Handler function to add/update user group details to DB.
     * This could happen if new group is created manually or the group is in different ENV in Study
     */
    #[Options("/barco-user-groups/add-to-db")]
    public function optionsAddBarcoUserGroupToDBAction(Request $request){}

    /**
     * Handler function to delete user group
     * This could happen if new group is created manually or the group is in different ENV in Study
     *
     * @param $barcoGroupId
     */
    #[Options("/barco-user-groups/{barcoGroupId}/delete")]
    public function optionsDeleteBarcoUserGroupAction($barcoGroupId){}

}
