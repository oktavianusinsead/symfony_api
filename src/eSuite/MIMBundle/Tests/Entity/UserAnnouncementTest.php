<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 03:40 PM
 */
namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\UserAnnouncement;

use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use esuite\MIMBundle\Tests\Mock\AnnouncementMock;
use esuite\MIMBundle\Tests\Mock\UserAnnouncementMock;

class UserAnnouncementTest extends \PHPUnit\Framework\TestCase
{
    /* Base */
    public function testCreated()
    {
        $userAnnouncement = new UserAnnouncement();

        $now = new \DateTime();

        $this->assertEquals($now, $userAnnouncement->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $userAnnouncement = new UserAnnouncement();

        $now = new \DateTime();

        $this->assertEquals($now, $userAnnouncement->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $userAnnouncement = new UserAnnouncement();

        $now = new \DateTime();

        $userAnnouncement->setUpdated($now);
        $userAnnouncement->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $userAnnouncement->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $userAnnouncement = new UserAnnouncementMock();

        $id = 98765345678;

        $this->assertEquals($id, $userAnnouncement->setId($id)->getId());
    }

    public function testUser()
    {
        $userAnnouncement = new UserAnnouncementMock();

        $user = new UserMock();
        $user->setBoxEmail("test.user@esuite.edu");

        $this->assertEquals($user, $userAnnouncement->setUser($user)->getUser());
    }

    public function testAnnouncement()
    {
        $userAnnouncement = new UserAnnouncementMock();

        $announcement = new AnnouncementMock();
        $announcement->setTitle("This is a test title of an announcement");

        $this->assertEquals($announcement, $userAnnouncement->setAnnouncement($announcement)->getAnnouncement());
    }

    public function testCourse()
    {
        $userAnnouncement = new UserAnnouncementMock();

        $course = new CourseMock();
        $course->setName("this is a test course for an announcement");

        $this->assertEquals($course, $userAnnouncement->setCourse($course)->getCourse());
    }

}
