<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\Country;

class CountryMock extends Country
{
    public function setStates($states)
    {
        $this->states = $states;
    }
}
