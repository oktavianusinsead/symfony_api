<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\Country;

class CountryMock extends Country
{
    public function setStates($states)
    {
        $this->states = $states;
    }
}
