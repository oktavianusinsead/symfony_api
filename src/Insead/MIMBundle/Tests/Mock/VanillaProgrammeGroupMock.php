<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\VanillaProgrammeGroup;

class VanillaProgrammeGroupMock extends VanillaProgrammeGroup
{
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setvanillaUserGroup($group)
    {
        $this->vanillaUserGroup = $group;
    }
}
