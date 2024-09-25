<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\VanillaUserGroup;

class VanillaUserGroupMock extends VanillaUserGroup
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
