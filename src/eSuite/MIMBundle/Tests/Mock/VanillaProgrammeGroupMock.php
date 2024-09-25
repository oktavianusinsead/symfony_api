<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\VanillaProgrammeGroup;

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
