<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\VanillaUserGroup;

class VanillaUserGroupMock extends VanillaUserGroup
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
