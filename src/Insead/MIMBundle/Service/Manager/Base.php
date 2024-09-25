<?php

namespace Insead\MIMBundle\Service\Manager;

use Insead\MIMBundle\Entity as ModelRepo;
/***
 * Class Base
 * @package Insead\MIMBundle\Service\Manager
 */
class Base extends AbstractBase
{
    protected function getBasename(string $model): string
    {
        return "Insead\MIMBundle\Entity\\$model";
    }
}
