<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\BarcoUserGroup;

class BarcoUserGroupMock extends BarcoUserGroup
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
