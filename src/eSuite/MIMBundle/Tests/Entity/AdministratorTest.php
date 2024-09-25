<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Tests\Mock\AdministratorMock;
use PHPUnit\Framework\TestCase;

class AdministratorTest extends TestCase
{
    public function testAdministrators()
    {
        $now = new \DateTime();

        $administrators = new AdministratorMock();
        $administrators->setId(20);
        $this->assertEquals(20, $administrators->getId());

        $administrators->setLastLogin($now);
        $this->assertEquals($now, $administrators->getLastLogin());

        $administrators->setBlocked(false);
        $this->assertEquals(false, $administrators->getBlocked());

        $administrators->setEmailSent(true);
        $this->assertEquals(true, $administrators->getEmailSent());

        $administrators->setFaculty(true);
        $this->assertEquals(true, $administrators->getFaculty());

        $administrators->setSupportOnly(true);
        $this->assertEquals(true, $administrators->getSupportOnly());
    }
}