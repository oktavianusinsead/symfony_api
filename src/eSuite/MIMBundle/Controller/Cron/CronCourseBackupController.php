<?php

namespace esuite\MIMBundle\Controller\Cron;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Service\File\FileManager;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\CronCourseBackupManager;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\edotNotify;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CronCourseBackupController extends BaseCronController
{

    public function __construct(LoggerInterface                          $logger,
                                ManagerRegistry                          $doctrine,
                                ParameterBagInterface                    $baseParameterBag,
                                ManagerBase                              $base,
                                edotNotify                              $notify,
                                EntityManager                            $em,
                                private readonly CronCourseBackupManager $cronCourseBackupManager)
    {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->base = $base;

        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $fileManager = new FileManager($baseParameterBag->get('edot.s3.config'), $logger, $notify, $em, $s3, $base);
        $this->cronCourseBackupManager->loadServiceManager($s3, $baseParameterBag->get('edot.backup.config'), $fileManager);
    }
    #[Post("/course-backup")]
    public function processCourseBackupRequestsAction()
    {
        return $this->cronCourseBackupManager->processCourseBackup();
    }
}
