<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\States;

class StatesMock extends States
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
