<?php

namespace Insead\MIMBundle\Controller\Cron;

use Insead\MIMBundle\Controller\BaseController;

class BaseCronController extends BaseController
{
    /**
    *   Function that logs a message for CRON only, prefixing the Class and function name to help debug
    *
    **/
    protected function log($msg)
    {
        $matches = [];

        preg_match('/[^\\\\]+$/', static::class, $matches);

        $this->get('monolog.logger.cron')->info($matches[0]
            . ":"
            . debug_backtrace()[1]['function']
            . " - "
            . $msg);
    }
}
