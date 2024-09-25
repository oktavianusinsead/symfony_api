<?php

namespace Insead\MIMBundle\Controller\Cron;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\CronPendingAttachmentManager;
use Insead\MIMBundle\Service\S3ObjectManager;
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

        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $this->cronPendingAttachmentManager->loadServiceManager($s3, $baseParameterBag->get('study.backup.config'));
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
