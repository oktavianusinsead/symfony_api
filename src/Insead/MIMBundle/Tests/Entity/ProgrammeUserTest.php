<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 07/06/17
 * Time: 01:40 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\ProgrammeUser;

use Insead\MIMBundle\Tests\Mock\ProgrammeUserMock;
use Insead\MIMBundle\Tests\Mock\ProgrammeMock;
use Insead\MIMBundle\Tests\Mock\UserMock;

class ProgrammeUserTest extends \PHPUnit\Framework\TestCase
{
    public function testRowIndex()
    {
        $programmeUser = new ProgrammeUserMock();

        $val = 98765345678;

        $this->assertEquals($val, $programmeUser->setRowIndex($val)->getRowIndex());
    }

    public function testOrderIndex()
    {
        $programmeUser = new ProgrammeUserMock();

        $val = 98765345678;

        $this->assertEquals($val, $programmeUser->setOrderIndex($val)->getOrderIndex());
    }

    /* Mocks */
    public function testId()
    {
        $programmeUser = new ProgrammeUserMock();

        $id = 98765345678;

        $this->assertEquals($id, $programmeUser->setId($id)->getId());
    }

    public function testProgramme()
    {
        $programme = new ProgrammeMock();
        $programme->setId( 456787654 );

        $programmeUser = new ProgrammeUserMock();
        $programmeUser->setProgramme($programme);

        $this->assertEquals($programme, $programmeUser->getProgramme());
    }

    public function testProgrammeId()
    {
        $id = 456787654;

        $programme = new ProgrammeMock();
        $programme->setId( $id );

        $programmeUser = new ProgrammeUserMock();
        $programmeUser->setProgramme($programme);

        $this->assertEquals($id, $programmeUser->getProgrammeId());
    }

    public function testUser()
    {
        $user = new UserMock();
        $user->setId( 456787654 );

        $programmeUser = new ProgrammeUserMock();
        $programmeUser->setUser($user);

        $this->assertEquals($user, $programmeUser->getUser());
    }

    public function testUserId()
    {
        $id = 456787654;

        $user = new UserMock();
        $user->setId( $id );

        $programmeUser = new ProgrammeUserMock();
        $programmeUser->setUser($user);

        $this->assertEquals($id, $programmeUser->getUserId());
    }
}
