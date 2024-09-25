<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 03:51 PM
 */
namespace esuite\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use esuite\MIMBundle\Entity\Task;

use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\SubtaskMock;
use esuite\MIMBundle\Tests\Mock\TaskMock;

class TaskTest extends \PHPUnit\Framework\TestCase
{
    public function testTitle()
    {
        $task = new Task();

        $title = "this is a test title for a task";

        $this->assertEquals($title, $task->setTitle($title)->getTitle());
    }

    public function testDate()
    {
        $task = new Task();

        $now = new \DateTime();

        $this->assertEquals($now, $task->setDate($now)->getDate());
    }

    public function testDescription()
    {
        $task = new Task();

        $description = "this is a test description for a task";

        $this->assertEquals($description, $task->setDescription($description)->getDescription());
    }

    public function testPublishAt()
    {
        $task = new Task();

        $now = new \DateTime();

        $this->assertEquals($now, $task->setPublishedAt($now)->getPublishedAt());
    }

    public function testPublished()
    {
        $task = new Task();

        $isPublished = true;

        $this->assertEquals($isPublished, $task->setPublished($isPublished)->getPublished());
    }

    public function testNotPublished()
    {
        $task = new Task();

        $isPublished = false;

        $this->assertEquals($isPublished, $task->setPublished($isPublished)->getPublished());
    }

    public function testBoxFolderId()
    {
        $task = new Task();

        $boxFolderId = "37482902383457645";

        $this->assertEquals($boxFolderId, $task->setBoxFolderId($boxFolderId)->getBoxFolderId());
    }

    public function testBoxFolderName()
    {
        $task = new TaskMock();

        $boxFolderId = "37482902383457645";

        $boxFolderName = "T-".$boxFolderId;

        $this->assertEquals($boxFolderName, $task->setId($boxFolderId)->getBoxFolderName());
    }

    /* Base */
    public function testCreated()
    {
        $task = new Task();

        $now = new \DateTime();

        $this->assertEquals($now, $task->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $task = new Task();

        $now = new \DateTime();

        $this->assertEquals($now, $task->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $task = new Task();

        $now = new \DateTime();

        $task->setUpdated($now);
        $task->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $task->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $task = new TaskMock();

        $id = 98765345678;
        $task->setId($id);

        $this->assertEquals($id, $task->getId());
    }

    public function testCourse()
    {
        $task = new TaskMock();

        $course = new CourseMock();
        $course->setName("this is a test course for a task");

        $this->assertEquals($course, $task->setCourse($course)->getCourse());
    }

    public function testCourseId()
    {
        $task = new TaskMock();

        $course = new CourseMock();
        $course->setName("this is a test course for a task");
        $course->setId(4560923759020);

        $this->assertEquals($course->getId(), $task->setCourse($course)->getCourseId());
    }

    public function testGetSet()
    {
        $task = new TaskMock();
        $arrayToTest = [
            ["setHighPriority","getHighPriority", true],
            ["setMarkedHighPriority","getMarkedHighPriority", true],
            ["setArchived","getArchived", true],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $task->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $task->$getMethod());
        }

        $cloneClone = clone $task;
        $cloneClone->setUpdated($task->getUpdated());
        $cloneClone->setCreated($task->getCreated());
        $cloneClone->setBoxFolderId($task->getBoxFolderId());
        $cloneClone->setPublishedAt($task->getPublishedAt());
        $cloneClone->setPublished($task->getPublished());
        $cloneClone->setArchived($task->getArchived());
        $this->assertEquals($task, $cloneClone);
    }

    public function testSubtasks()
    {
        $subtask1 = new SubtaskMock();
        $subtask1->setId( 6389275020471 );

        $subtask2 = new SubtaskMock();
        $subtask2->setId( 6389275020472 );

        $subtask3 = new SubtaskMock();
        $subtask3->setId( 6389275020473 );
        $subtask3->setPosition(null);

        $subtasks = new ArrayCollection();
        $subtasks->add($subtask1);
        $subtasks->add($subtask2);
        $subtasks->add($subtask3);

        $task = new TaskMock();
        $task->setId( 84780295703 );

        $this->assertEquals($subtasks->toArray(), $task->setSubtasks( $subtasks )->getSubtasks());
    }

    public function testSubtaskIdsSerialized()
    {
        $subtask1 = new SubtaskMock();
        $subtask1->setId( 6389275020471 );

        $subtask2 = new SubtaskMock();
        $subtask2->setId( 6389275020472 );

        $subtask3 = new SubtaskMock();
        $subtask3->setId( 6389275020473 );

        $subtasks = new ArrayCollection();
        $subtasks->add($subtask1);
        $subtasks->add($subtask2);
        $subtasks->add($subtask3);

        $task = new TaskMock();
        $task->setId( 84780295703 );
        $task->serializeFullObject(true);

        $this->assertEquals($subtasks->toArray(), $task->setSubtasks( $subtasks )->getSubtaskIds());
    }

    public function testSubtaskIds()
    {
        $subtask1 = new SubtaskMock();
        $subtask1->setId( 6389275020471 );

        $subtask2 = new SubtaskMock();
        $subtask2->setId( 6389275020472 );

        $subtask3 = new SubtaskMock();
        $subtask3->setId( 6389275020473 );

        $subtasks = new ArrayCollection();
        $subtasks->add($subtask1);
        $subtasks->add($subtask2);
        $subtasks->add($subtask3);

        $task = new TaskMock();
        $task->setId( 84780295703 );
        $task->serializeFullObject(false);

        $this->assertEquals(
            [
                $subtask1->getId(),
                $subtask2->getId(),
                $subtask3->getId(),
            ],

            $task->setSubtasks( $subtasks )->getSubtaskIds()
        );
    }

    public function testPosition(){
        $task = new TaskMock();
        $task->setPosition(12);
        $this->assertEquals($task->getPosition(), 12);
    }

}
