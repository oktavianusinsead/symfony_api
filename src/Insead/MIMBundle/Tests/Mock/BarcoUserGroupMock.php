<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\BarcoUserGroup;

class BarcoUserGroupMock extends BarcoUserGroup
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
