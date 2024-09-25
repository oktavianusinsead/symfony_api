<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Tests\Mock\StatesMock;
use PHPUnit\Framework\TestCase;

class StatesTest extends TestCase
{
    public function testStates()
    {
        $states = new StatesMock();
        $states->setId(20);
        $this->assertEquals(20, $states->getId());

        $states->setCountry("Singapore");
        $this->assertEquals("Singapore", $states->getCountry());

        $states->setStateCode("PGL");
        $this->assertEquals("PGL", $states->getStateCode());

        $states->setStateName("Punggol");
        $this->assertEquals("Punggol", $states->getStateName());
    }
}
