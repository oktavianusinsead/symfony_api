<?php

namespace esuite\MIMBundle\Service\Manager;

use esuite\MIMBundle\Entity as ModelRepo;
/***
 * Class Base
 * @package esuite\MIMBundle\Service\Manager
 */
class Base extends AbstractBase
{
    protected function getBasename(string $model): string
    {
        return "esuite\MIMBundle\Entity\\$model";
    }
}
