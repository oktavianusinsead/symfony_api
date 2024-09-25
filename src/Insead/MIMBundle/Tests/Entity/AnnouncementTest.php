<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 01:48 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Insead\MIMBundle\Entity\Announcement;

use Insead\MIMBundle\Tests\Mock\CourseMock;
use Insead\MIMBundle\Tests\Mock\AnnouncementMock;
use Insead\MIMBundle\Tests\Mock\UserMock;
use Insead\MIMBundle\Tests\Mock\UserAnnouncementMock;

class AnnouncementTest extends \PHPUnit\Framework\TestCase
{
    public function testPeoplesoftId()
    {
        $announcement = new Announcement();

        $psoftId = "49095";

        $this->assertEquals($psoftId, $announcement->setPeoplesoftId($psoftId)->getPeoplesoftId());

        $this->assertEquals($psoftId, $announcement->getAuthor());
    }

    public function testTitle()
    {
        $announcement = new Announcement();

        $title = "test title for announcements";

        $this->assertEquals($title, $announcement->setTitle($title)->getTitle());
    }

    public function testDescription()
    {
        $announcement = new Announcement();

        $description = "test description for announcements";

        $this->assertEquals($description, $announcement->setDescription($description)->getDescription());
    }

    public function testPublishAt()
    {
        $announcement = new Announcement();

        $now = new \DateTime();

        $this->assertEquals($now, $announcement->setPublishedAt($now)->getPublishedAt());
    }

    public function testPublished()
    {
        $announcement = new Announcement();

        $isPublished = true;

        $this->assertEquals($isPublished, $announcement->setPublished($isPublished)->getPublished());
    }

    public function testNotPublished()
    {
        $announcement = new Announcement();

        $isPublished = false;

        $this->assertEquals($isPublished, $announcement->setPublished($isPublished)->getPublished());
    }

    /* Base */
    public function testCreated()
    {
        $announcement = new Announcement();

        $now = new \DateTime();

        $this->assertEquals($now, $announcement->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $announcement = new Announcement();

        $now = new \DateTime();

        $this->assertEquals($now, $announcement->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $announcement = new Announcement();

        $now = new \DateTime();

        $announcement->setUpdated($now);
        $announcement->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $announcement->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $announcement = new AnnouncementMock();

        $id = 98765345678;

        $this->assertEquals($id, $announcement->setId($id)->getId());
    }

    public function testCourse()
    {
        $announcement = new Announcement();

        $course = new CourseMock();
        $course->setName("this is a test course for an announcement");

        $this->assertEquals($course, $announcement->setCourse($course)->getCourse());
    }

    public function testCourseId()
    {
        $announcement = new Announcement();

        $course = new CourseMock();
        $course->setName("this is a test course for an announcement");
        $course->setId(4560923759020);

        $this->assertEquals($course->getId(), $announcement->setCourse($course)->getCourseId());
    }

    public function testUserAnnouncements()
    {

        $announcement = new AnnouncementMock();
        $announcement->setId( 456787654 );

        $user1 = new UserMock();
        $user1->setId( 46997654 );

        $user2 = new UserMock();
        $user2->setId( 9875683 );


        $userAnnouncement1 = new UserAnnouncementMock();
        $userAnnouncement1->setUser($user1);
        $userAnnouncement1->setAnnouncement($announcement);
        $userAnnouncement1->setId( 60001 );

        $userAnnouncement2 = new UserAnnouncementMock();
        $userAnnouncement2->setUser($user2);
        $userAnnouncement2->setAnnouncement($announcement);
        $userAnnouncement2->setId( 60002 );

        $userAnnouncements = new ArrayCollection();
        $userAnnouncements->add($userAnnouncement1);
        $userAnnouncements->add($userAnnouncement2);

        $announcement->serializeOnlyPublished(true);

        $this->assertEquals($userAnnouncements, $announcement->setUserAnnouncements( $userAnnouncements )->getUserAnnouncements());
    }
}
