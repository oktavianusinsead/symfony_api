<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\BarcoUser;

class BarcoUserMock extends BarcoUser
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
