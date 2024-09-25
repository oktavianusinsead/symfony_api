<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 04/04/17
 * Time: 01:46 PM
 */
namespace esuite\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Course;

use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\ProgrammeAdministrator;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\UserDevice;
use esuite\MIMBundle\Tests\Mock\ActivityMock;
use esuite\MIMBundle\Tests\Mock\AnnouncementMock;
use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\CourseSubscriptionMock;
use esuite\MIMBundle\Tests\Mock\GroupActivityMock;
use esuite\MIMBundle\Tests\Mock\GroupMock;
use esuite\MIMBundle\Tests\Mock\ProgrammeMock;
use esuite\MIMBundle\Tests\Mock\TaskMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use esuite\MIMBundle\Tests\Mock\RoleMock;
use PHPUnit\Framework\TestCase;

class CourseTest extends TestCase
{
    protected ProgrammeMock $programme;
    protected CourseMock $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->programme = new ProgrammeMock();
        $this->programme->setId(999999);
        $this->programme->setOverriderReadonly(true);

        $this->course = new CourseMock();
        $this->course->setId( 748292 );
        $this->course->setPublished( true );
        $this->course->setProgramme($this->programme);
    }

    public function testGetSetModifiers()
    {
        $arrayToTest = [
            ["setUid","getUid", "this is a test UID for a course"],
            ["setName","getName", "this is a test name for a course"],
            ["setAbbreviation","getAbbreviation", "this is a test abbreviation for a course"],
            ["setStartDate","getStartDate", new \DateTime()],
            ["setEndDate","getEndDate", new \DateTime()],
            ["setTimezone","getTimezone", "this is a test timezone for a course"],
            ["setCountry","getCountry", "this is a test country for a course"],
            ["setPublished","getPublished", true],
            ["setPsSessionCode","getPsSessionCode", "this is a test peoplesoft session code for a course"],
            ["setPsClassSection","getPsClassSection", "this is a test peoplesoft class section for a course"],
            ["setPsCrseId","getPsCrseId", "6538293"],
            ["setPsAcadCareer","getPsAcadCareer", "this is a test peoplesoft acad career for a course"],
            ["setPsStrm","getPsStrm", "this is a test peoplesoft term for a course"],
            ["setPsClassNbr","getPsClassNbr", "this is a test peoplesoft class number for a course"],
            ["setPsClassDescr","getPsClassDescr", "this is a test peoplesoft class description for a course"],
            ["setPsCampus","getPsCampus", "this is a test peoplesoft campus for a course"],
            ["setPsSrrComponent","getPsSrrComponent", "this is a test peoplesoft srr component for a course"],
            ["setPsClassStat","getPsClassStat", "this is a test peoplesoft class status for a course"],
            ["setPsLmsUrl","getPsLmsUrl", "https://this.is.a.test.url.com"],
            ["setPsLocation","getPsLocation", "this is a test peoplesoft location for a course"],
            ["setUpdated","getUpdated", new \DateTime()],
            ["setCreated","getCreated", new \DateTime()],
            ["setId","getId", 98765345678],
            ["setBoxGroupId","getBoxGroupId", 12345],
            ["setOriginalTimezone","getOriginalTimezone", "+08:00"],
            ["setOriginalCountry","getOriginalCountry", "Singapore"],
            ["setCourseTypeView","getCourseTypeView", 2],
            ["showOnlyTasksWithSubtasks",null, true],
            [null,"getProgrammeId", $this->course->getProgramme()->getId()],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $this->course->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $this->course->$getMethod());
        }
    }

    public function testUpdatedValue()
    {
        $now = new \DateTime();

        $this->course->setUpdated($now);
        $this->course->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $this->course->getUpdated());
    }

    public function testCourseSubscriptions()
    {
        $coordinator = new RoleMock();
        $coordinator->setId( 848 );
        $coordinator->setName('coordinator');

        $director = new RoleMock();
        $coordinator->setId( 653 );
        $director->setName('director');

        $user1 = new UserMock();
        $user1->setId( 7593 );
        $user1->setPeoplesoftId( 1234567 );

        $user2 = new UserMock();
        $user2->setId( 95847 );
        $user2->setPeoplesoftId( 3456789 );

        $user3 = new UserMock();
        $user3->setId( 9868 );
        $user3->setPeoplesoftId( 9865647 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 46997654 );
        $courseSubscription1->setRole( $coordinator );
        $courseSubscription1->setUser( $user1 );

        $courseSubscription2 = new CourseSubscriptionMock();
        $courseSubscription2->setId( 9875683 );
        $courseSubscription2->setRole( $coordinator );
        $courseSubscription2->setUser( $user2 );

        $courseSubscription3 = new CourseSubscriptionMock();
        $courseSubscription3->setId( 34622 );
        $courseSubscription3->setRole( $director );
        $courseSubscription3->setUser( $user3 );

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);
        $courseSubscriptions->add($courseSubscription2);
        $courseSubscriptions->add($courseSubscription3);

        $this->course->setCourseSubscriptions($courseSubscriptions);


        $this->assertEquals($courseSubscriptions, $this->course->getSubscriptions());
    }

    public function testCourseSubscriptionsEntityByRole()
    {
        $coordinator = new RoleMock();
        $coordinator->setId( 848 );
        $coordinator->setName('coordinator');

        $director = new RoleMock();
        $coordinator->setId( 653 );
        $director->setName('director');

        $user1 = new UserMock();
        $user1->setId( 7593 );
        $user1->setPeoplesoftId( 1234567 );

        $user2 = new UserMock();
        $user2->setId( 95847 );
        $user2->setPeoplesoftId( 3456789 );

        $user3 = new UserMock();
        $user3->setId( 9868 );
        $user3->setPeoplesoftId( 9865647 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 46997654 );
        $courseSubscription1->setRole( $coordinator );
        $courseSubscription1->setUser( $user1 );

        $courseSubscription2 = new CourseSubscriptionMock();
        $courseSubscription2->setId( 9875683 );
        $courseSubscription2->setRole( $coordinator );
        $courseSubscription2->setUser( $user2 );

        $courseSubscription3 = new CourseSubscriptionMock();
        $courseSubscription3->setId( 34622 );
        $courseSubscription3->setRole( $director );
        $courseSubscription3->setUser( $user3 );

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);
        $courseSubscriptions->add($courseSubscription2);
        $courseSubscriptions->add($courseSubscription3);

        $this->course->setCourseSubscriptions($courseSubscriptions);


        $this->assertEquals(
            [
                $user1,
                $user2
            ],
            $this->course->getSubscribersEntityByRole('coordinator')
        );

        $notAValidRole = new RoleMock();
        $notAValidRole->setId( 111 );
        $notAValidRole->setName('notavalidrole');

        // Add device to user
        $userDevice = new UserDevice();
        $userDevice->setUser($user3);
        $userDevice->setIosDeviceId("iosDeviceId1234567890");

        $user3 = new UserMock();
        $user3->setId( 888 );
        $user3->setPeoplesoftId( 1234567 );
        $user3->addUserDevice($userDevice);

        $courseSubscription3 = new CourseSubscriptionMock();
        $courseSubscription3->setId( 123456789 );
        $courseSubscription3->setRole( $notAValidRole );
        $courseSubscription3->setUser( $user3 );

        $courseSubscriptions1 = new ArrayCollection();
        $courseSubscriptions1->add($courseSubscription3);

        $programmeMock = new ProgrammeMock();
        $programmeMock->setId(88888);
        $programmeMock->setOverriderReadonly(false);
        $programmeMock->setRequestorId(12345);
        $programmeMock->setCourseSubscriptions($courseSubscriptions1);

        $courseMock = new CourseMock();
        $courseMock->setProgramme($programmeMock);
        $courseMock->addUser($courseSubscription3);

        /** @var CourseSubscription $subscription */
        $subscription = $courseMock->getSubscriptions()[0];
        $this->assertEquals($user3, $subscription->getUser());
        $this->assertEquals([], $courseMock->getSubscribersEntityByRole('notavalidrole', true));
        $this->assertEquals([], $courseMock->getStudents());
        $this->assertEquals([
            'students' => [],
            'professors' => [],
            'directors' => [],
            'contacts' => [],
            'hidden' => [],
            'esuiteteam' => [],
            'coordinators' => [],
        ], $courseMock->getAllUserObjects());

        /** @var UserDevice $userDevice */
        $userDeviceTest = $courseMock->getSubscribedUserDevices()[0];
        $this->assertEquals($userDevice->getIosDeviceId(), $userDeviceTest);

        $courseMock->removeUser($courseSubscription3);
        $this->assertEquals([], $courseMock->getSubscriptions()->toArray());
    }

    public function testPublishedSessions()
    {
        $now = new \DateTime();
        $abbreviation = "abbre";
        $country = "country";

        $programme = new Programme();
        $programme->setId(999999);
        $programme->setOverriderReadonly(true);

        $course = new CourseMock();
        $course->setId( 748292 );
        $course->setPublished( true );
        $course->setProgramme($programme);
        $course->setCountry($country);

        $session = new Session();
        $session->setId(123);
        $session->setName("Session Name");
        $session->setDescription("Session Description");
        $session->setAbbreviation($abbreviation);
        $session->setStartDate($now);
        $session->setCourse($course);
        $session->setPublished(true);

        $sessionCollection = new ArrayCollection();
        $sessionCollection->add($session);

        $course->setSessions($sessionCollection);
        $this->assertCount(1, $course->getPublishedSessions());

        $checkSession = $course->getSessionIds()[0];
        $this->assertEquals($session->getId(), $checkSession);

        $this->assertCount(1, $course->getSessions());

        $course->serializeFullObject(true);
        /** @var Session $checkSession */
        $checkSession = $course->getSessionIds()[0];
        $this->assertEquals($session->getId(), $checkSession->getId());

        $session->setPublished(false);
        $sessionCollection = new ArrayCollection();
        $sessionCollection->add($session);
        $course->setSessions($sessionCollection);
        $course->serializeOnlyPublished(true);

        $this->assertCount(0, $course->getSessions());
    }

    public function testActivities()
    {
        $activity = new ActivityMock();
        $activity->setId( 456787654 );
        $activity->setPublished(true);

        $group1 = new GroupMock();
        $group1->setId( 46997654 );
        $group1->setCourseDefault(true);

        $group2 = new GroupMock();
        $group2->setId( 5555555 );
        $group2->setCourseDefault(false);

        $groups = new ArrayCollection();
        $groups->add($group1);

        $groupActivity1 = new GroupActivityMock();
        $groupActivity1->setPublished(true);
        $groupActivity1->setGroup($group1);
        $groupActivity1->setActivity($activity);
        $groupActivity1->setId( 60001 );

        $groupActivities = new ArrayCollection();
        $groupActivities->add($groupActivity1);

        $this->course->setActivities($groupActivities);
        $this->assertEquals($groupActivities->toArray(), $this->course->getActivities());
        $this->assertEquals($groupActivities->toArray(), $this->course->getPublishedActivities());
        $this->assertEquals($groupActivity1->getId(), $this->course->getActivityIds()[0]);

        $this->course->setGroups($groups);
        $this->assertEquals($groups->toArray(), $this->course->getGroups());
        $this->assertEquals($group1->getId(), $this->course->getGroupIds()[0]);

        $this->assertEquals($group1->getId(), $this->course->getDefaultGroup());

        $this->course->serializeFullObject(true);

        /** @var GroupActivityMock $groupActivityMockCheck */
        $groupActivityMockCheck = $this->course->getActivityIds()[0];
        $this->assertEquals($groupActivity1, $groupActivityMockCheck);

        /** @var GroupMock $groupToCheck */
        $groupToCheck = $this->course->getGroupIds()[0];
        $this->assertEquals($group1, $groupToCheck);

        $groups = new ArrayCollection();
        $groups->add($group2);
        $this->course->setGroups($groups);
        $this->assertEquals("", $this->course->getDefaultGroup());
    }

    public function testTasks()
    {
        $now = new \DateTime();
        $newNow = new \DateTime();
        $newNow->add( new \DateInterval('P6D'));

        $task1 = new TaskMock();
        $task1->setId(111);
        $task1->setPublished(true);
        $task1->setPosition(2);
        $task1->setDate($now);
        $task1->setSubtasks(new ArrayCollection());

        $task2 = new TaskMock();
        $task2->setId(2222);
        $task2->setPublished(false);
        $task2->setPosition(1);
        $task2->setDate($newNow);

        $task3 = new TaskMock();
        $task3->setId(333);
        $task3->setPublished(true);
        $task3->setPosition(3);
        $task3->setDate($newNow);

        $tasks = new ArrayCollection();
        $tasks->add($task1);
        $tasks->add($task2);
        $tasks->add($task3);

        $subtasks = new ArrayCollection();
        $subtasks->add($task1);
        $subtasks->add($task2);

        $task3->setSubtasks($subtasks);
        $this->course->setTasks($tasks);

        $this->assertSameSize($tasks, $this->course->getTasks());
        $this->assertEquals(count($tasks), count($this->course->getTaskIds()));

        $this->course->serializeFullObject(true);
        $this->assertSameSize($tasks, $this->course->getTasks());

        $this->course->showOnlyTasksWithSubtasks(true);
        $this->assertEquals($task3, $this->course->getTasks()[0]);
        $this->assertEquals(1, count($this->course->getTaskIds()));

        $this->course->showOnlyTasksWithSubtasks(false);
        $this->course->serializeOnlyPublished(true);
        $this->assertEquals(2, count($this->course->getTaskIds()));
    }

    public function testAnnouncements()
    {
        $announcement1 = new AnnouncementMock();
        $announcement1->setId(1);
        $announcement1->setPublished(true);
        $announcement1->setCourse($this->course);

        $announcement2 = new AnnouncementMock();
        $announcement2->setId(2);
        $announcement2->setPublished(false);
        $announcement2->setCourse($this->course);

        $announcementList = new ArrayCollection();
        $announcementList->add($announcement1);
        $announcementList->add($announcement2);

        $this->course->setAnnouncements($announcementList);

        $this->assertEquals($announcementList->toArray(), $this->course->getAnnouncements());
        $this->assertEquals($announcement1, $this->course->getPublishedAnnouncements()[0]);

        $this->course->serializeOnlyPublished(true);
        $this->assertEquals($announcement1->getId(), $this->course->getAnnouncementIds()[0]);

        $this->course->serializeFullObject(true);
        $this->assertEquals($announcement1, $this->course->getAnnouncementIds()[0]);
    }

    public function testCourseStudents()
    {
        $this->programme->setCourses([$this->course]);

        $role = new RoleMock();
        $role->setId( 848 );
        $role->setName('student');

        $user1 = new UserMock();
        $user1->setId( 7593 );
        $user1->setPeoplesoftId( 1234567 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 46997654 );
        $courseSubscription1->setRole( $role );
        $courseSubscription1->setUser( $user1 );

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);

        $this->course->setCourseSubscriptions($courseSubscriptions);

        $this->assertEquals( [ $user1->getPeoplesoftId() ], $this->course->getStudents() );
    }

    public function testCourseCoordinators()
    {
        $role = new RoleMock();
        $role->setId( 848 );
        $role->setName('coordinator');

        $user1 = new UserMock();
        $user1->setId( 7593 );
        $user1->setPeoplesoftId( 1234567 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 46997654 );
        $courseSubscription1->setRole( $role );
        $courseSubscription1->setUser( $user1 );

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);

        $this->course->setCourseSubscriptions($courseSubscriptions);

        $this->assertEquals( [ $user1->getPeoplesoftId() ], $this->course->getCoordination() );
    }

    public function testCourseFaculty()
    {
        $role = new RoleMock();
        $role->setId( 848 );
        $role->setName('faculty');

        $user1 = new UserMock();
        $user1->setId( 7593 );
        $user1->setPeoplesoftId( 1234567 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 46997654 );
        $courseSubscription1->setRole( $role );
        $courseSubscription1->setUser( $user1 );

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);

        $this->course->setCourseSubscriptions($courseSubscriptions);

        $this->assertEquals( [ $user1->getPeoplesoftId() ], $this->course->getFaculty(false) );
    }

    public function testCourseDirectors()
    {
        $role = new RoleMock();
        $role->setId( 848 );
        $role->setName('director');

        $user1 = new UserMock();
        $user1->setId( 7593 );
        $user1->setPeoplesoftId( 1234567 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 46997654 );
        $courseSubscription1->setRole( $role );
        $courseSubscription1->setUser( $user1 );

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);

        $this->course->setCourseSubscriptions($courseSubscriptions);

        $this->assertEquals( [ $user1->getPeoplesoftId() ], $this->course->getDirectors() );
    }

    public function testCourseContacts()
    {
        $role = new RoleMock();
        $role->setId( 848 );
        $role->setName('contact');

        $user1 = new UserMock();
        $user1->setId( 7593 );
        $user1->setPeoplesoftId( 1234567 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 46997654 );
        $courseSubscription1->setRole( $role );
        $courseSubscription1->setUser( $user1 );

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);

        $this->course->setCourseSubscriptions($courseSubscriptions);

        $this->assertEquals( [ $user1->getPeoplesoftId() ], $this->course->getContacts() );
    }

    public function testCourseHidden()
    {
        $role = new RoleMock();
        $role->setId( 848 );
        $role->setName('hidden');

        $user1 = new UserMock();
        $user1->setId( 7593 );
        $user1->setPeoplesoftId( 1234567 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 46997654 );
        $courseSubscription1->setRole( $role );
        $courseSubscription1->setUser( $user1 );

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);

        $this->course->setCourseSubscriptions($courseSubscriptions);

        $this->assertEquals( [ $user1->getPeoplesoftId() ], $this->course->getHidden() );
    }

    public function testCourseAllUsers()
    {
        $roles = [
            [
                "role" => "student",
                "key" => "students"
            ],
            [
                "role" => "faculty",
                "key" => "professors"
            ],
            [
                "role" => "director",
                "key" => "directors"
            ],
            [
                "role" => "coordinator",
                "key" => "coordinators"
            ],
            [
                "role" => "contact",
                "key" => "contacts"
            ],
            [
                "role" => "hidden",
                "key" => "hidden"
            ],
            [
                "role" => "esuiteteam",
                "key" => "esuiteteam"
            ]
        ];

        $people = [];

        $courseSubscriptions = new ArrayCollection();
        foreach( $roles as $roleItem ) {
            $role = new RoleMock();
            $role->setId( random_int( 10000, 99999 ) );
            $role->setName( $roleItem["role"] );

            $user = new UserMock();
            $user->setId( random_int( 10000, 99999 ) );
            $user->setPeoplesoftId( random_int( 1000000, 9999999 ) );

            $people[ $roleItem["key"] ] = $user->getPeoplesoftId();

            $courseSubscription = new CourseSubscriptionMock();
            $courseSubscription->setId( random_int( 10000, 99999 ) );
            $courseSubscription->setRole( $role );
            $courseSubscription->setUser( $user );

            $courseSubscriptions->add($courseSubscription);
        }

        $this->course->setCourseSubscriptions($courseSubscriptions);

        $expectedPeople = [];
        foreach( $roles as $roleItem ) {
            $expectedPeople[ $roleItem["key"] ] = [];
            $expectedPeople[$roleItem["key"]][] = $people[$roleItem["key"]];
        }

        $this->assertEquals( $expectedPeople, $this->course->getAllUsers() );
    }

    public function testSerialize() {
        $programme = new Programme();
        $programme->setId(999999);
        $programme->setOverriderReadonly(true);
        $programme->setName("test Programme");

        $serializedObj = $programme->jsonSerialize();
        $this->assertEquals('test Programme' , $serializedObj['name']);
    }

    public function testSessionBoxFolders()
    {
        $now = new \DateTime();
        $abbreviation = "abbre";
        $country = "country";

        $programme = new Programme();
        $programme->setId(999999);
        $programme->setOverriderReadonly(true);

        $course = new CourseMock();
        $course->setId( 748292 );
        $course->setPublished( true );
        $course->setProgramme($programme);
        $course->setCountry($country);

        $session = new Session();
        $session->setId(123);
        $session->setName("Session Name");
        $session->setDescription("Session Description");
        $session->setAbbreviation($abbreviation);
        $session->setStartDate($now);
        $session->setCourse($course);
        $session->setPublished(true);
        $session->setBoxFolderId("sessionfolder");

        $sessionCollection = new ArrayCollection();
        $sessionCollection->add($session);

        $course->setSessions($sessionCollection);

        $task1 = new TaskMock();
        $task1->setId(111);
        $task1->setPublished(true);
        $task1->setPosition(2);
        $task1->setDate($now);
        $task1->setBoxFolderId("boxfolder");

        $tasks = new ArrayCollection();
        $tasks->add($task1);

        $course->setTasks($tasks);

        $this->assertEquals(['sessionfolder', 'boxfolder'], $course->getSessionBoxFolders());
    }

    public function testClone()
    {
        $this->course->setAbbreviation('1701252394_dsfsdf');

        $cloneCourse = clone $this->course;
        $this->course->setPsCrseId("courseid");
        $this->course->setPsStrm("strm");
        $this->course->setPsClassNbr("classnumber");

        $this->course->setUid($cloneCourse->getUid());
        $cloneCourse->setAbbreviation($this->course->getAbbreviation());
        $this->course->setBoxGroupId($cloneCourse->getBoxGroupId());

        $cloneCourse->setId($this->course->getId());
        $cloneCourse->setCreated($this->course->getCreated());
        $cloneCourse->setUpdated($this->course->getUpdated());
        $cloneCourse->setPublished($this->course->getPublished());
        $cloneCourse->setPsCrseId($this->course->getPsCrseId());
        $cloneCourse->setPsStrm($this->course->getPsStrm());
        $cloneCourse->setPsClassNbr($this->course->getPsClassNbr());
        $this->assertEquals($this->course, $cloneCourse);
    }
}
