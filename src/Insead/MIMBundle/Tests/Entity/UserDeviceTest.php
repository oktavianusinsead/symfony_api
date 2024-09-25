<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 03:40 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\UserDevice;

use Insead\MIMBundle\Tests\Mock\UserMock;
use Insead\MIMBundle\Tests\Mock\UserDeviceMock;

class UserDeviceTest extends \PHPUnit\Framework\TestCase
{
    public function testIosDeviceId()
    {
        $userDevice = new UserDevice();

        $iosDeviceId = "this is a test title for a user device";

        $this->assertEquals($iosDeviceId, $userDevice->setIosDeviceId($iosDeviceId)->getIosDeviceId());
    }

    /* Base */
    public function testCreated()
    {
        $userDevice = new UserDevice();

        $now = new \DateTime();

        $this->assertEquals($now, $userDevice->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $userDevice = new UserDevice();

        $now = new \DateTime();

        $this->assertEquals($now, $userDevice->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $userDevice = new UserDevice();

        $now = new \DateTime();

        $userDevice->setUpdated($now);
        $userDevice->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $userDevice->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $userDevice = new UserDeviceMock();

        $id = 98765345678;

        $this->assertEquals($id, $userDevice->setId($id)->getId());
    }

    public function testUser()
    {
        $userDevice = new UserDeviceMock();

        $user = new UserMock();
        $user->setBoxEmail("test.user@insead.edu");

        $this->assertEquals($user, $userDevice->setUser($user)->getUser());
    }

}
