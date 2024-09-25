<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\ProgrammeAdministrator;

class ProgrammeAdministratorMock extends ProgrammeAdministrator
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
