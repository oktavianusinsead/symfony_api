<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 03:40 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\CourseBackupEmail;

use Insead\MIMBundle\Tests\Mock\CourseMock;
use Insead\MIMBundle\Tests\Mock\UserMock;
use Insead\MIMBundle\Tests\Mock\CourseBackupEmailMock;

class CourseBackupEmailTest extends \PHPUnit\Framework\TestCase
{
    public function testUserEmail()
    {
        $courseBackupEmail = new CourseBackupEmail();

        $email = "this.email.for.backup@insead.edu";

        $this->assertEquals($email, $courseBackupEmail->setUserEmail($email)->getUserEmail());
    }

    /* Base */
    public function testCreated()
    {
        $courseBackupEmail = new CourseBackupEmail();

        $now = new \DateTime();

        $this->assertEquals($now, $courseBackupEmail->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $courseBackupEmail = new CourseBackupEmail();

        $now = new \DateTime();

        $this->assertEquals($now, $courseBackupEmail->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $courseBackupEmail = new CourseBackupEmail();

        $now = new \DateTime();

        $courseBackupEmail->setUpdated($now);
        $courseBackupEmail->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $courseBackupEmail->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $courseBackupEmail = new CourseBackupEmailMock();

        $id = 98765345678;

        $this->assertEquals($id, $courseBackupEmail->setId($id)->getId());
    }

    public function testUser()
    {
        $courseBackupEmail = new CourseBackupEmailMock();

        $user = new UserMock();
        $user->setBoxEmail("test.user@insead.edu");

        $this->assertEquals($user, $courseBackupEmail->setUser($user)->getUser());
    }

    public function testCourse()
    {
        $courseBackupEmail = new CourseBackupEmailMock();

        $course = new CourseMock();
        $course->setName("This is a test course name");

        $this->assertEquals($course, $courseBackupEmail->setCourse($course)->getCourse());
    }

}
