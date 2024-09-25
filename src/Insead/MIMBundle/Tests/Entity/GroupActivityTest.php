<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 11:12 AM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\Group;
use Insead\MIMBundle\Entity\GroupActivity;
use Insead\MIMBundle\Entity\Activity;

use Insead\MIMBundle\Tests\Mock\GroupActivityMock;
use Insead\MIMBundle\Tests\Mock\ActivityMock;
use Insead\MIMBundle\Tests\Mock\GroupMock;
use PHPUnit\Framework\TestCase;

class GroupActivityTest extends TestCase
{
    public function testActivity()
    {
        $groupActivity = new GroupActivity();

        $activity = new Activity();
        $activityDescription = "This is a test description";
        $activity->setDescription($activityDescription);

        $groupActivity->setActivity($activity);

        $this->assertEquals($activity, $groupActivity->getActivity());
    }

    public function testLocation()
    {
        $groupActivity = new GroupActivity();

        $location = "this is a test location for a programme";

        $this->assertEquals($location, $groupActivity->setLocation($location)->getLocation());
    }

    public function testStartDate()
    {
        $groupActivity = new GroupActivity();

        $now = new \DateTime();

        $this->assertEquals($now, $groupActivity->setStartDate($now)->getStartDate());
    }

    public function testEndDate()
    {
        $groupActivity = new GroupActivity();

        $now = new \DateTime();

        $this->assertEquals($now, $groupActivity->setEndDate($now)->getEndDate());
    }

    /* Base */
    public function testCreated()
    {
        $groupActivity = new GroupActivity();

        $now = new \DateTime();

        $this->assertEquals($now, $groupActivity->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $groupActivity = new GroupActivity();

        $now = new \DateTime();

        $this->assertEquals($now, $groupActivity->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $groupActivity = new GroupActivity();

        $now = new \DateTime();

        $groupActivity->setUpdated($now);
        $groupActivity->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $groupActivity->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $groupActivity = new GroupActivityMock();

        $id = 98765345678;

        $this->assertEquals($id, $groupActivity->setId($id)->getId());
    }

    public function testActivityId()
    {
        $groupActivity = new GroupActivity();

        $activity = new ActivityMock();

        $activityDescription = "This is a test description";
        $activity->setDescription($activityDescription);

        $id = 98765345678;
        $activity->setId($id);

        $groupActivity->setActivity($activity);

        $this->assertEquals($id, $groupActivity->getActivityId());
    }

    public function testGroup()
    {
        $groupActivity = new GroupActivity();

        $group = new GroupMock();

        $groupName = "This is a test name for a group";
        $group->setName($groupName);

        $id = 98765345678;
        $group->setId($id);

        $groupActivity->setGroup($group);

        $this->assertEquals($group, $groupActivity->getGroup());
    }

    public function testGroupId()
    {
        $groupActivity = new GroupActivity();

        $group = new GroupMock();

        $groupName = "This is a test name for a group";
        $group->setName($groupName);

        $id = 98765345678;
        $group->setId($id);

        $groupActivity->setGroup($group);

        $this->assertEquals($id, $groupActivity->getGroupId());
    }

    public function testOriginalStartDate() {
        $groupActivity = new GroupActivity();

        $group = new GroupMock();

        $groupName = "This is a test name for a group";
        $group->setName($groupName);

        $id = 98765345678;
        $group->setId($id);

        $groupActivity->setGroup($group);

        $now = new \DateTime();
        $groupActivity->setOriginalStartDate($now);

        $this->assertEquals($now, $groupActivity->getOriginalStartDate());
    }

    public function testOriginalEndDate() {
        $groupActivity = new GroupActivity();

        $group = new GroupMock();

        $groupName = "This is a test name for a group";
        $group->setName($groupName);

        $id = 98765345678;
        $group->setId($id);

        $groupActivity->setGroup($group);

        $now = new \DateTime();
        $groupActivity->setOriginalEndDate($now);

        $this->assertEquals($now, $groupActivity->getOriginalEndDate());
    }

    public function testClone() {
        $groupActivity = new GroupActivity();
        $group = new GroupMock();

        $groupName = "This is a test name for a group";
        $group->setName($groupName);

        $id = 98765345678;
        $group->setId($id);

        $groupActivity->setGroup($group);

        $cloneGroupActivity = clone $groupActivity;
        $cloneGroupActivity->setId($groupActivity->getId());
        $cloneGroupActivity->setCreated($groupActivity->getCreated());
        $cloneGroupActivity->setUpdated($groupActivity->getUpdated());
        $this->assertEquals($cloneGroupActivity, $groupActivity);
    }
}
