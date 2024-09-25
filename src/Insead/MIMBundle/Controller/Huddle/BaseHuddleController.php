<?php

namespace Insead\MIMBundle\Controller\Huddle;

use Insead\MIMBundle\Controller\BaseController;

class BaseHuddleController extends BaseController
{
    /**
    *   Function that logs a message for Huddle only, prefixing the Class and function name to help debug
    *
    **/
    protected function log($msg)
    {
        $matches = [];

        preg_match('/[^\\\\]+$/', static::class, $matches);

        $this->get('monolog.logger.vanilla')->info($matches[0]
            . ":"
            . debug_backtrace()[1]['function']
            . " - "
            . $msg);
    }
}
