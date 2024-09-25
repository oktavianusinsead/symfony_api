<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 03:40 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\UserSubtask;

use Insead\MIMBundle\Tests\Mock\CourseMock;
use Insead\MIMBundle\Tests\Mock\SubtaskMock;
use Insead\MIMBundle\Tests\Mock\UserMock;
use Insead\MIMBundle\Tests\Mock\UserSubtaskMock;

class UserSubTaskTest extends \PHPUnit\Framework\TestCase
{
    /* Base */
    public function testCreated()
    {
        $userSubtask = new UserSubtask();

        $now = new \DateTime();

        $this->assertEquals($now, $userSubtask->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $userSubtask = new UserSubtask();

        $now = new \DateTime();

        $this->assertEquals($now, $userSubtask->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $userSubtask = new UserSubtask();

        $now = new \DateTime();

        $userSubtask->setUpdated($now);
        $userSubtask->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $userSubtask->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $userSubtask = new UserSubtaskMock();

        $id = 98765345678;

        $this->assertEquals($id, $userSubtask->setId($id)->getId());
    }

    public function testUser()
    {
        $userSubtask = new UserSubtaskMock();

        $user = new UserMock();
        $user->setBoxEmail("test.user@insead.edu");

        $this->assertEquals($user, $userSubtask->setUser($user)->getUser());
    }

    public function testSubtask()
    {
        $userSubtask = new UserSubtaskMock();

        $subtask = new SubtaskMock();
        $subtask->setTitle("This is a test subtask title");

        $this->assertEquals($subtask, $userSubtask->setSubtask($subtask)->getSubtask());
    }

    public function testCourse()
    {
        $userSubtask = new UserSubtaskMock();

        $course = new CourseMock();
        $course->setName("This is a test course name");

        $this->assertEquals($course, $userSubtask->setCourse($course)->getCourse());
    }

}
