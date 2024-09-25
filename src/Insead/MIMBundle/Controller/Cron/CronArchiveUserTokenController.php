<?php

namespace Insead\MIMBundle\Controller\Cron;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use Insead\MIMBundle\Service\Manager\CronArchiveUserTokenManager;

class CronArchiveUserTokenController extends BaseCronController
{
    #[Post("/archive/user-tokens")]
    public function archiveUserTokensAction(CronArchiveUserTokenManager $cronArchiveUserTokenManager)
    {
        return $cronArchiveUserTokenManager->archiveUserTokens();
    }

}
