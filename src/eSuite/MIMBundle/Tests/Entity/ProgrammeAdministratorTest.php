<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Tests\Mock\ProgrammeAdministratorMock;
use esuite\MIMBundle\Tests\Mock\ProgrammeMock;
use PHPUnit\Framework\TestCase;

class ProgrammeAdministratorTest extends TestCase
{
    public function testId()
    {
        $programmeAdmin = new ProgrammeAdministratorMock();
        $programmeAdmin->setId(12);

        $this->assertEquals(12, $programmeAdmin->getId());
    }

    public function testProgramme()
    {
        $programme = new ProgrammeMock();
        $programme->setId( 456787654 );
        $programme->setOverriderReadonly(true);

        $programmeAdmin = new ProgrammeAdministratorMock();
        $programmeAdmin->setId(12);
        $programmeAdmin->setProgramme($programme);

        $this->assertEquals($programme, $programmeAdmin->getProgramme());
    }
}
