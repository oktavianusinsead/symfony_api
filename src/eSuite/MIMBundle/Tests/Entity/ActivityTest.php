<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 28/2/17
 * Time: 11:43 AM
 */
namespace esuite\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Activity;

use esuite\MIMBundle\Tests\Mock\ActivityMock;
use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\GroupActivityMock;
use esuite\MIMBundle\Tests\Mock\GroupMock;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    public function testDescription()
    {
        $activity = new Activity();

        $description = "This is a test description";

        $this->assertEquals($description, $activity->setDescription($description)->getDescription());
    }

    public function testEndDate()
    {
        $activity = new Activity();

        $now = new \DateTime();

        $this->assertEquals($now, $activity->setEndDate($now)->getEndDate());
    }

    public function testPosition()
    {
        $activity = new Activity();

        $position = 123;

        $this->assertEquals($position, $activity->setPosition($position)->getPosition());
    }

    public function testPublished()
    {
        $activity = new Activity();

        $isPublished = true;

        $this->assertEquals($isPublished, $activity->setPublished($isPublished)->getPublished());
    }

    public function testNotPublished()
    {
        $activity = new Activity();

        $isPublished = false;

        $this->assertEquals($isPublished, $activity->setPublished($isPublished)->getPublished());
    }

    public function testStartDate()
    {
        $activity = new Activity();

        $now = new \DateTime();

        $this->assertEquals($now, $activity->setStartDate($now)->getStartDate());
    }

    public function testTitle()
    {
        $activity = new Activity();

        $title = "This is test title";

        $this->assertEquals($title, $activity->setTitle($title)->getTitle());
    }

    public function testType()
    {
        $activity = new Activity();

        $type = 1234567890;
        $this->assertEquals($type, $activity->setType($type)->getType());
    }

    /* Base */
    public function testCreated()
    {
        $activity = new Activity();

        $now = new \DateTime();

        $this->assertEquals($now, $activity->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $activity = new Activity();

        $now = new \DateTime();

        $this->assertEquals($now, $activity->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $activity = new Activity();

        $now = new \DateTime();

        $activity->setUpdated($now);
        $activity->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $activity->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $activity = new ActivityMock();

        $id = 98765345678;

        $activity->setId($id);

        $this->assertEquals($id, $activity->getId());
    }

    public function testCourse()
    {
        $activity = new Activity();

        $course = new CourseMock();
        $course->setName("this is a test course for an activity");

        $this->assertEquals($course, $activity->setCourse($course)->getCourse());
    }

    public function testCourseId()
    {
        $activity = new Activity();

        $course = new CourseMock();
        $course->setName("this is a test course for an activity");
        $course->setId(4560923759020);

        $this->assertEquals($course->getId(), $activity->setCourse($course)->getCourseId());
    }

    public function testGroupActivities()
    {

        $activity = new ActivityMock();
        $activity->setId( 456787654 );

        $group1 = new GroupMock();
        $group1->setId( 46997654 );

        $group2 = new GroupMock();
        $group2->setId( 9875683 );

        $groupActivity1 = new GroupActivityMock();
        $groupActivity1->setGroup($group1);
        $groupActivity1->setActivity($activity);
        $groupActivity1->setId( 60001 );

        $groupActivity2 = new GroupActivityMock();
        $groupActivity2->setGroup($group2);
        $groupActivity2->setActivity($activity);
        $groupActivity2->setId( 60002 );

        $groupActivities = new ArrayCollection();
        $groupActivities->add($groupActivity1);
        $groupActivities->add($groupActivity2);

        $activity->serializeOnlyPublished(true);
        $this->assertEquals([60001,60002], $activity->setGroupActivities( $groupActivities )->getGroupActivityIds()) && $this->assertEquals([$groupActivity1, $groupActivity2],$activity->getGroupActivities());
        $this->assertEquals($groupActivities, $activity->getGroupActivities());
    }

    public function testHiddenGroupActivities()
    {

        $activity = new ActivityMock();
        $activity->setId( 4567876541 );

        $group1 = new GroupMock();
        $group1->setId( 469976541 );

        $group2 = new GroupMock();
        $group2->setId( 98756831 );

        $groupActivity1 = new GroupActivityMock();
        $groupActivity1->setGroup($group1);
        $groupActivity1->setActivity($activity);
        $groupActivity1->setId( 600011 );

        $groupActivity2 = new GroupActivityMock();
        $groupActivity2->setGroup($group2);
        $groupActivity2->setActivity($activity);
        $groupActivity2->setId( 600021 );

        $groupActivities = new ArrayCollection();
        $groupActivities->add($groupActivity1);
        $groupActivities->add($groupActivity2);

        $activity->serializeOnlyPublished(true);
        $activity->doNotShowGroupActivities(true);
        $this->assertEquals([], $activity->setGroupActivities( $groupActivities )->getGroupActivityIds());
    }

    public function testActivityScheduled() {
        $activity = new ActivityMock();
        $activity->setActivityScheduled(true);
        $this->assertEquals(true, $activity->getActivityScheduled());
    }

    public function testClone() {
        $activity = new Activity();
        $cloneActivity = clone $activity;
        $cloneActivity->setCreated($activity->getCreated());
        $cloneActivity->setUpdated($activity->getUpdated());
        $this->assertEquals($cloneActivity, $activity);
    }

}
