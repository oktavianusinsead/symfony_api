<?php

namespace esuite\MIMBundle\Controller\Cron;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\CronPendingAttachmentManager;
use esuite\MIMBundle\Service\S3ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CronPendingAttachmentController extends BaseCronController
{

    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                private readonly CronPendingAttachmentManager $cronPendingAttachmentManager)
    {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->base = $base;

        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $this->cronPendingAttachmentManager->loadServiceManager($s3, $baseParameterBag->get('edot.backup.config'));
    }

    #[Post("/pending-attachments")]
    public function processPendingAttachmentsAction()
    {
        return $this->cronPendingAttachmentManager->processPendingAttachments();
    }

    #[Get("/pending-attachments")]
    public function checkForPendingAttachmentsAction()
    {
        return $this->cronPendingAttachmentManager->checkForPendingAttachments();
    }

}
