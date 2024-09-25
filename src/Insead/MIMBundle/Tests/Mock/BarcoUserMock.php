<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\BarcoUser;

class BarcoUserMock extends BarcoUser
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
