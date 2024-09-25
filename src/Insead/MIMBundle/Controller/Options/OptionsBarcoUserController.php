<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsBarcoUserController extends BaseController
{
    /**
     * Handler function to set peoplesoft_id of the barco user which do not have a peoplesoft_id set on their details
     * User with no peoplesoft_if will occur when user is created manually from Barco
     */
    #[Options("/barco-update-user-peoplesoft_id")]
    public function optionsCleanBarcoPeoplesoftIDAction(){}

    /**
     * Handler function to get list of users in a group by groupId
     */
    #[Options("/barco-users")]
    public function optionsBarcoUserGroupUsersAction(){}

    /**
     * Handler function search a user
     *
     * @param Request $request
     */
    #[Options("/barco-users/search")]
    public function optionsGetBarcoUserAction($request){}

    /**
     * Handler function update a user and update in barco
     */
    #[Options("/barco-users/update")]
    public function optionsUpdateBarcoUserAction(Request $request){}

    /**
     * Handler function to delete a user
     */
    #[Options("/barco-users/{id}")]
    public function optionsDeleteBarcoUsersAction(Request $request, $id){}

    /**
     * Handler function to get all non INSEAD email account
     */
    #[Options("/barco-users/nonInsead")]
    public function optionsBarcoNonINSEADUsersAction(){}
}
