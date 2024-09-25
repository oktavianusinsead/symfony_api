<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 01:48 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Insead\MIMBundle\Entity\CourseSubscription;

use Insead\MIMBundle\Tests\Mock\CourseMock;
use Insead\MIMBundle\Tests\Mock\CourseSubscriptionMock;
use Insead\MIMBundle\Tests\Mock\ProgrammeMock;
use Insead\MIMBundle\Tests\Mock\UserMock;
use Insead\MIMBundle\Tests\Mock\RoleMock;

class CourseSubscriptionTest extends \PHPUnit\Framework\TestCase
{
   /* Mocks */
    public function testId()
    {
        $courseSubscription = new CourseSubscriptionMock();

        $id = 98765345678;

        $this->assertEquals($id, $courseSubscription->setId($id)->getId());
    }

    public function testProgramme()
    {
        $courseSubscription = new CourseSubscription();

        $programme = new ProgrammeMock();
        $programme->setName("this is a test programme for a course subscription");

        $this->assertEquals($programme, $courseSubscription->setProgramme($programme)->getProgramme());
    }

    public function testCourse()
    {
        $courseSubscription = new CourseSubscription();

        $course = new CourseMock();
        $course->setName("this is a test course for a course subscription");

        $this->assertEquals($course, $courseSubscription->setCourse($course)->getCourse());
    }

    public function testUser()
    {
        $courseSubscription = new CourseSubscription();

        $user = new UserMock();
        $user->setBoxEmail("test.user@insead.edu");

        $this->assertEquals($user, $courseSubscription->setUser($user)->getUser());
    }

    public function testRole()
    {
        $courseSubscription = new CourseSubscription();

        $role = new RoleMock();
        $role->setName("T-E-S-T-R-O-L-E");

        $this->assertEquals($role, $courseSubscription->setRole($role)->getRole());
    }

}
