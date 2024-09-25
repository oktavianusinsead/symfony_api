<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 11:52 AM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\Role;

use Insead\MIMBundle\Tests\Mock\CourseMock;
use Insead\MIMBundle\Tests\Mock\CourseSubscriptionMock;
use Insead\MIMBundle\Tests\Mock\ProgrammeMock;
use Insead\MIMBundle\Tests\Mock\UserMock;
use Insead\MIMBundle\Tests\Mock\RoleMock;

class RoleTest extends \PHPUnit\Framework\TestCase
{
    public function testName()
    {
        $role = new Role();

        $name = "T-E-S-T-R-O-L-E";

        $this->assertEquals($name, $role->setName($name)->getName());
    }

    /* Base */
    public function testCreated()
    {
        $role = new Role();

        $now = new \DateTime();

        $this->assertEquals($now, $role->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $role = new Role();

        $now = new \DateTime();

        $this->assertEquals($now, $role->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $role = new Role();

        $now = new \DateTime();

        $role->setUpdated($now);
        $role->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $role->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $role = new RoleMock();

        $id = 98765345678;

        $this->assertEquals($id, $role->setId($id)->getId());
    }

    public function testCourseSubscription()
    {
        $programme = new ProgrammeMock();
        $programme->setId( 638927502047 );

        $course = new CourseMock();
        $course->setId( 84780295703 );

        $user1 = new UserMock();
        $user1->setId( 301 );

        $user2 = new UserMock();
        $user2->setId( 302 );

        $user3 = new UserMock();
        $user3->setId( 303 );

        $role = new RoleMock();
        $role->setId( 4567876541 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setProgramme($programme);
        $courseSubscription1->setCourse($course);
        $courseSubscription1->setUser($user1);

        $courseSubscription2 = new CourseSubscriptionMock();
        $courseSubscription2->setProgramme($programme);
        $courseSubscription2->setCourse($course);
        $courseSubscription2->setUser($user2);

        $courseSubscription3 = new CourseSubscriptionMock();
        $courseSubscription3->setProgramme($programme);
        $courseSubscription3->setCourse($course);
        $courseSubscription3->setUser($user3);

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);
        $courseSubscriptions->add($courseSubscription2);
        $courseSubscriptions->add($courseSubscription3);


        $this->assertEquals($courseSubscriptions, $role->setCourseSubscription( $courseSubscriptions )->getCourseSubscription());
    }
}
