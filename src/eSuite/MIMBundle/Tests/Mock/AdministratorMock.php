<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\Administrator;

class AdministratorMock extends Administrator
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
