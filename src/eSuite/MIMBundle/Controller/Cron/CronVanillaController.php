<?php

namespace esuite\MIMBundle\Controller\Cron;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Controller\VanillaForumBaseController;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Options;

class CronVanillaController extends VanillaForumBaseController
{


    /**
     * Handler function to process programme that has no initial group yet
     *
     * @return mixed
     * @throws NotSupported
     */
     #[Post("/cron-vanilla-programme-group")]
    public function processCronProgrammeGroupAction(Request $request)
    {
        return $this->vanillaForumManager->createInitialGroup($request);
    }

    /**
     * This function will be called periodically that will queue the user to add to everyone group if exists
     * @return array
     */
    #[Post("/cron-vanilla-process-everyone-queue")]
    public function processQueueAddMemberToGroupAction(Request $request)
    {
        return $this->vanillaForumManager->addUserToEveryone($request);
    }

    /**
     * This function will be called periodically that will add the user to a member in vanilla group api
     *
     * @return mixed
     * @throws NotSupported
     */
    #[Post("/cron-vanilla-process-pending-user-group")]
    public function processPendingAddMemberToGroupAction(Request $request)
    {
        return $this->vanillaForumManager->processPendingUser($request);
    }

    /**
     * This function will be called periodically that will remove the user to a member in vanilla group api
     * @return array
     */
    #[Delete("/cron-vanilla-process-removing-user-group")]
    public function processPendingRemoveMemberToGroupAction(Request $request)
    {
        return $this->vanillaForumManager->processRemovingUser($request);
    }

    /**
     * This function will be called periodically that will queue the user to add to everyone group if exists
     *
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Post("/cron-vanilla-process-pending-conversation")]
    public function processPendingConversationCreateAction(Request $request)
    {
        return $this->vanillaForumManager->processPendingConversation($request);
    }

}
