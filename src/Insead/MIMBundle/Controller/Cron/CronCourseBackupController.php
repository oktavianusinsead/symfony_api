<?php

namespace Insead\MIMBundle\Controller\Cron;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Service\File\FileManager;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\CronCourseBackupManager;
use Insead\MIMBundle\Service\S3ObjectManager;
use Insead\MIMBundle\Service\StudyNotify;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CronCourseBackupController extends BaseCronController
{

    public function __construct(LoggerInterface                          $logger,
                                ManagerRegistry                          $doctrine,
                                ParameterBagInterface                    $baseParameterBag,
                                ManagerBase                              $base,
                                StudyNotify                              $notify,
                                EntityManager                            $em,
                                private readonly CronCourseBackupManager $cronCourseBackupManager)
    {
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $this->base = $base;

        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $fileManager = new FileManager($baseParameterBag->get('study.s3.config'), $logger, $notify, $em, $s3, $base);
        $this->cronCourseBackupManager->loadServiceManager($s3, $baseParameterBag->get('study.backup.config'), $fileManager);
    }
    #[Post("/course-backup")]
    public function processCourseBackupRequestsAction()
    {
        return $this->cronCourseBackupManager->processCourseBackup();
    }
}
