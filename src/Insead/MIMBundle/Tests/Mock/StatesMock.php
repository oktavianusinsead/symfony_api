<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\States;

class StatesMock extends States
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
