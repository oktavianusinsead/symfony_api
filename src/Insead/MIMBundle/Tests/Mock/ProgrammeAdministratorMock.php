<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\ProgrammeAdministrator;

class ProgrammeAdministratorMock extends ProgrammeAdministrator
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
