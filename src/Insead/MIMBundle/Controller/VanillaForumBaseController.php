<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\VanillaForumManager;
use Insead\MIMBundle\Service\S3ObjectManager;
use Insead\MIMBundle\Service\Vanilla\Category as VanillaCategory;
use Insead\MIMBundle\Service\Vanilla\Conversation as VanillaConversationAPI;
use Insead\MIMBundle\Service\Vanilla\Discussion as VanillaDiscussion;
use Insead\MIMBundle\Service\Vanilla\Group as VanillaGroup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VanillaForumBaseController extends BaseController
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $doctrine, ParameterBagInterface $baseParameterBag, ManagerBase $base, public VanillaForumManager $vanillaForumManager)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;

        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $vanillaCategory = new VanillaCategory($baseParameterBag->get('vanilla.config'), $logger);
        $vanillaConversationAPI = new VanillaConversationAPI($baseParameterBag->get('vanilla.config'), $logger);
        $vanillaGroup = new VanillaGroup($baseParameterBag->get('vanilla.config'), $logger);
        $vanillaDiscussion = new VanillaDiscussion($baseParameterBag->get('vanilla.config'), $logger);
        $this->vanillaForumManager->loadServiceManager($s3, $baseParameterBag->get('study.s3.config'), $vanillaGroup, $vanillaCategory, $vanillaDiscussion, $vanillaConversationAPI, $baseParameterBag->get('vanilla.config'));
    }
}
