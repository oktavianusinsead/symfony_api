<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\VanillaForumManager;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\Vanilla\Category as VanillaCategory;
use esuite\MIMBundle\Service\Vanilla\Conversation as VanillaConversationAPI;
use esuite\MIMBundle\Service\Vanilla\Discussion as VanillaDiscussion;
use esuite\MIMBundle\Service\Vanilla\Group as VanillaGroup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VanillaForumBaseController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, public VanillaForumManager $vanillaForumManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $vanillaCategory = new VanillaCategory($baseParameterBag->get('vanilla.config'), $logger);
        $vanillaConversationAPI = new VanillaConversationAPI($baseParameterBag->get('vanilla.config'), $logger);
        $vanillaGroup = new VanillaGroup($baseParameterBag->get('vanilla.config'), $logger);
        $vanillaDiscussion = new VanillaDiscussion($baseParameterBag->get('vanilla.config'), $logger);
        $this->vanillaForumManager->loadServiceManager($s3, $baseParameterBag->get('edot.s3.config'), $vanillaGroup, $vanillaCategory, $vanillaDiscussion, $vanillaConversationAPI, $baseParameterBag->get('vanilla.config'));
    }
}
