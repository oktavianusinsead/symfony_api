<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\Administrator;

class AdministratorMock extends Administrator
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
