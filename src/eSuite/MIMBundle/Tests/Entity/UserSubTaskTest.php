<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 03:40 PM
 */
namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\UserSubtask;

use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\SubtaskMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use esuite\MIMBundle\Tests\Mock\UserSubtaskMock;

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
        $user->setBoxEmail("test.user@esuite.edu");

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
