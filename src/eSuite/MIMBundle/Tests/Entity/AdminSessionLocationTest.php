<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 01:48 PM
 */
namespace esuite\MIMBundle\Tests\Entity;
use esuite\MIMBundle\Tests\Mock\AdminSessionLocationMock;
use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\ProgrammeMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use PHPUnit\Framework\TestCase;

class AdminSessionLocationTest extends TestCase
{
    protected ProgrammeMock $programme;
    protected CourseMock $course;
    protected UserMock $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new UserMock();
        $this->user->setId(12345678);
        $this->user->setFirstname("Firstname");
        $this->user->setLastname("Lastname");

        $this->programme = new ProgrammeMock();
        $this->programme->setId(999999);
        $this->programme->setOverriderReadonly(true);

        $this->course = new CourseMock();
        $this->course->setId( 748292 );
        $this->course->setPublished( true );
        $this->course->setProgramme($this->programme);
    }

    public function testId()
    {
        $adminSessionLocation = new AdminSessionLocationMock();
        $id = 12345;
        $adminSessionLocation->setId($id);
        $this->assertEquals($id, $adminSessionLocation->getId());
    }

    public function testCourse()
    {
        $adminSessionLocation = new AdminSessionLocationMock();
        $adminSessionLocation->setCourse($this->course);
        $this->assertEquals($this->course, $adminSessionLocation->getCourse());
    }

    public function testCourseId()
    {
        $adminSessionLocation = new AdminSessionLocationMock();
        $adminSessionLocation->setCourse($this->course);
        $this->assertEquals($this->course->getId(), $adminSessionLocation->getCourseId());
    }

    public function testUser()
    {
        $adminSessionLocation = new AdminSessionLocationMock();
        $adminSessionLocation->setCourse($this->course);
        $adminSessionLocation->setUser($this->user);
        $this->assertEquals($this->user, $adminSessionLocation->getUser());
    }

    public function testUserId()
    {
        $adminSessionLocation = new AdminSessionLocationMock();
        $adminSessionLocation->setCourse($this->course);
        $adminSessionLocation->setUser($this->user);
        $this->assertEquals($this->user->getId(), $adminSessionLocation->getUserId());
    }

    public function testLocation()
    {
        $adminSessionLocation = new AdminSessionLocationMock();
        $adminSessionLocation->setLocation("location 1");
        $this->assertEquals("location 1", $adminSessionLocation->getLocation());
    }
}
