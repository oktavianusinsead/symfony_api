<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsVanillaForumAdministrationController extends BaseController
{

    #[Options("/discussion/{programmeId}/group/create")]
    public function optionsCreateForumGroupAction($programmeId){}

    #[Options("/discussion/{programmeId}/group/{groupId}/delete")]
    public function optionsDeleteForumGroupAction($programmeId, $groupId){}

    #[Options("/discussion/{programmeId}/group/{groupId}/member/{vanillaUserId}/add")]
    public function optionsAddMemberGroupAction($programmeId,$groupId,$vanillaUserId){}

    #[Options("/discussion/{programmeId}/group/{groupId}/member/{vanillaUserId}/remove")]
    public function optionsRemoveMemberGroupAction($programmeId,$groupId,$vanillaUserId){}

    #[Options("/discussion/{programmeId}/discussion/create")]
    public function optionsCreateDiscussionAction($programmeId){}

    #[Options("/discussion/{programmeId}/discussion/{discussionId}/remove")]
    public function optionsRemoveDiscussionAction($programmeId, $discussionId){}

    #[Options("/discussion/{programmeId}/discussion/{discussionId}/close")]
    public function optionsCloseDiscussionAction($programmeId, $discussionId){}

    #[Options("/discussion/{programmeId}/discussion/{discussionId}/update")]
    public function optionsUpdateDiscussionAction($programmeId, $discussionId){}

    #[Options("/discussion/{programmeId}/group/{groupId}/update")]
    public function optionsUpdateGroupAction($programmeId, $groupId){}

    #[Options("/discussion/{programmeId}/conversation/create")]
    public function optionsConversationCreateAction($programmeId){}

    #[Options("/cron-huddle-users")]
    public function optionsCronHuddleUsersAction(){}

    #[Options("/cron-vanilla-programme-group")]
    public function optionsCronProgrammeGroupAction(){}

    #[Options("/cron-vanilla-user-everyone-queue")]
    public function optionsCronProcessQueueAddMemberToGroupAction(){}

    #[Options("/cron-vanilla-process-pending-user-group")]
    public function optionsCronProcessPendingAddMemberToGroupAction(){}

    #[Options("/huddle/user-names")]
    public function optionsHuddleUserInformationAction() {}
}
