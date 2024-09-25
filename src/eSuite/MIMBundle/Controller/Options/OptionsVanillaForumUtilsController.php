<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use Symfony\Component\HttpFoundation\Request;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsVanillaForumUtilsController extends BaseController
{

    #[Options("/discussion/{programmeId}/users/listByRole")]
    public function optionsForumUserListByRoleAction($programmeId){}

    #[Options("/discussion/{programmeId}/status")]
    public function optionsForumStatusAction($programmeId) {}

    #[Options("/discussion/{programmeId}/group/list")]
    public function optionsForumGroupListAction($programmeId){}

    #[Options("/discussion/{programmeId}/discussion/list")]
    public function optionsForumDiscussionListAction($programmeId){}

    #[Options("/discussion/{programmeId}/url")]
    public function optionsGetVanillaURLAction( $programmeId){}

    #[Options("/discussion/{programmeId}/discussion/{discussionId}/reopen")]
    public function optionsReOpenDiscussionAction($programmeId, $discussionId){}

    #[Options("/discussion/{programmeId}/peopleListWithRole")]
    public function optionsGetPeopleListWIthRoleAction(Request $request, $programmeId) {}
}
