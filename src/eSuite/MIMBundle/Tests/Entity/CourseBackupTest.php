<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 12:03 PM
 */
namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\CourseBackup;

use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\CourseBackupMock;

class CourseBackupTest extends \PHPUnit\Framework\TestCase
{
    public function testS3Path()
    {
        $backup = new CourseBackup();

        $path = "/sample-s3-path/1235/";

        $this->assertEquals($path, $backup->setS3Path($path)->getS3Path());
    }

    public function testSize()
    {
        $backup = new CourseBackup();

        $size = "12345678909876";

        $this->assertEquals($size, $backup->setSize($size)->getSize());
    }

    public function testCompleted()
    {
        $backup = new CourseBackup();

        $now = new \DateTime();

        $this->assertEquals($now, $backup->setCompleted($now)->getCompleted());
    }

    public function testInProgress()
    {
        $backup = new CourseBackup();

        $isInProgress = true;

        $this->assertEquals($isInProgress, $backup->setInProgress($isInProgress)->getInProgress());
    }

    public function testNotInProgress()
    {
        $backup = new CourseBackup();

        $isInProgress = false;

        $this->assertEquals($isInProgress, $backup->setInProgress($isInProgress)->getInProgress());
    }

    public function testStart()
    {
        $backup = new CourseBackup();

        $now = new \DateTime();

        $this->assertEquals($now, $backup->setStart($now)->getStart());
    }

    /* Base */
    public function testCreated()
    {
        $backup = new CourseBackup();

        $now = new \DateTime();

        $this->assertEquals($now, $backup->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $backup = new CourseBackup();

        $now = new \DateTime();

        $this->assertEquals($now, $backup->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $backup = new CourseBackup();

        $now = new \DateTime();

        $backup->setUpdated($now);
        $backup->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $backup->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $backup = new CourseBackupMock();

        $id = 98765345678;

        $this->assertEquals($id, $backup->setId($id)->getId());
    }

    public function testCourse()
    {
        $backup = new CourseBackup();

        $course = new CourseMock();
        $course->setName("this is a test course for a course backup");

        $this->assertEquals($course, $backup->setCourse($course)->getCourse());
    }

    public function testNoCourse()
    {
        $backup = new CourseBackup();

        $this->assertEquals('', $backup->getCourse());
    }

    public function testCourseId()
    {
        $backup = new CourseBackup();

        $course = new CourseMock();
        $course->setName("this is a test course for a course backup");
        $course->setId(4560923759020);

        $this->assertEquals($course->getId(), $backup->setCourse($course)->getCourseId());
    }

    public function testNoCourseId()
    {
        $backup = new CourseBackup();

        $this->assertEquals('', $backup->getCourseId());
    }
}
