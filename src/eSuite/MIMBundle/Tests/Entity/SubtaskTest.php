<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 04:36 PM
 */
namespace esuite\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Subtask;

use esuite\MIMBundle\Entity\TemplateSubtask;
use esuite\MIMBundle\Entity\TemplateTask;
use esuite\MIMBundle\Tests\Mock\TaskMock;
use esuite\MIMBundle\Tests\Mock\SubtaskMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use esuite\MIMBundle\Tests\Mock\UserSubtaskMock;
use PHPUnit\Framework\TestCase;

class SubtaskTest extends TestCase
{
    public function testTitle()
    {
        $subtask = new Subtask();

        $title = "this is a test title for a subtask";

        $this->assertEquals($title, $subtask->setTitle($title)->getTitle());
    }

    public function testSubtaskType()
    {
        //0 - File Doc
        //1 - Link
        //2 - Text
        $subtask = new Subtask();

        $subtaskType = "0";
        $this->assertEquals($subtaskType, $subtask->setSubtaskType($subtaskType)->getSubtaskType());

        $subtaskType = "1";
        $this->assertEquals($subtaskType, $subtask->setSubtaskType($subtaskType)->getSubtaskType());

        $subtaskType = "2";
        $this->assertEquals($subtaskType, $subtask->setSubtaskType($subtaskType)->getSubtaskType());
    }

    public function testBoxId()
    {
        $subtask = new Subtask();

        $boxId = "37482902383457645";

        $this->assertEquals($boxId, $subtask->setBoxId($boxId)->getBoxId());
    }

    public function testUrl()
    {
        $subtask = new Subtask();

        $url = "https://this.is.a.test.web.site";

        $this->assertEquals($url, $subtask->setUrl($url)->getUrl());
    }

    public function testFilesize()
    {
        $subtask = new Subtask();

        $size = 6748028747394757;

        $this->assertEquals($size, $subtask->setFilesize($size)->getFilesize());
    }

    public function testFilename()
    {
        $subtask = new Subtask();

        $filename = "test.pdf";

        $this->assertEquals($filename, $subtask->setFilename($filename)->getFilename());
    }

    public function testMimeType()
    {
        $subtask = new Subtask();

        $mime = "application/pdf";

        $this->assertEquals($mime, $subtask->setMimeType($mime)->getMimeType());
    }

    public function testPages()
    {
        $subtask = new Subtask();

        $pages = 537;

        $this->assertEquals($pages, $subtask->setPages($pages)->getPages());
    }

    /* Base */
    public function testCreated()
    {
        $subtask = new Subtask();

        $now = new \DateTime();

        $this->assertEquals($now, $subtask->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $subtask = new Subtask();

        $now = new \DateTime();

        $this->assertEquals($now, $subtask->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $subtask = new Subtask();

        $now = new \DateTime();

        $subtask->setUpdated($now);
        $subtask->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $subtask->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $subtask = new SubtaskMock();

        $id = 98765345678;

        $this->assertEquals($id, $subtask->setId($id)->getId());
    }

    public function testTask()
    {
        $subtask = new SubtaskMock();

        $task = new TaskMock();
        $task->setTitle("this is a test task for a subtask");

        $this->assertEquals($task, $subtask->setTask($task)->getTask());
    }

    public function testTaskId()
    {
        $subtask = new SubtaskMock();

        $task = new TaskMock();
        $task->setTitle("this is a test task for a subtask");
        $task->setId(4560923759020);

        $this->assertEquals($task->getId(), $subtask->setTask($task)->getTaskId());
    }

    public function testNoTask()
    {
        $subtask = new SubtaskMock();

        $this->assertEquals('', $subtask->getTaskId());
    }

    public function testGetSet()
    {
        $subtask = new SubtaskMock();
        $arrayToTest = [
            ["setUploadToS3","getUploadToS3", true],
            ["setAwsPath","getAwsPath", "/aws/path/dir"],
            ["setFileId","getFileId", "lastname"],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $subtask->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $subtask->$getMethod());
        }

        $cloneSubtask = clone $subtask;
        $cloneSubtask->setUpdated($subtask->getUpdated());
        $cloneSubtask->setCreated($subtask->getCreated());
        $cloneSubtask->setAwsPath($subtask->getAwsPath());
        $cloneSubtask->setFileId($subtask->getFileId());
        $this->assertEquals($subtask, $cloneSubtask);

        $templateSubtask = new TemplateSubtask();
        $arrayToTest = [
            ["setTask","getTask", $subtask],
            [null,"getTaskId", $subtask->getId()],
            ["setTask",null, null],
            [null,"getTaskId", ''],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $templateSubtask->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $templateSubtask->$getMethod());
        }

        $templateTask = new TemplateTask();
        $arrayToTest = [
            ["setSourceTaskId","getSourceTaskId", 12345],
            ["setStandard",null, "this is standard"],
            [null,"getBoxFolderName", "TemplateTask-".$templateTask->getId()],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $templateTask->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $templateTask->$getMethod());
        }
    }

    public function testTemplateSubtasks()
    {
        $templateTask = new TemplateTask();

        $subtask1 = new SubtaskMock();
        $subtask1->setId(1);
        $subtask1->setTitle("title 1");
        $subtask1->setFileId(1);
        $subtask1->setPosition(null);

        $subtask2 = new SubtaskMock();
        $subtask2->setId(2);
        $subtask2->setTitle("title 2");
        $subtask2->setFileId(2);
        $subtask2->setPosition(1);

        $subtasks = new ArrayCollection();
        $subtasks->add($subtask1);
        $subtasks->add($subtask2);

        $templateTask->setTemplateSubtasks($subtasks);
        $this->assertEquals(count($subtasks), count($templateTask->getTemplateSubtasks()));
        $this->assertEquals([$subtask2->getId(), $subtask1->getId()],$templateTask->getTemplateSubtasksIds());
        $templateTask->serializeFullObject(true);
        $this->assertSameSize($subtasks, $templateTask->getTemplateSubtasksIds());
    }

    public function testUserSubtasks()
    {

        $subtask = new SubtaskMock();
        $subtask->setId( 4567876541 );

        $user1 = new UserMock();
        $user1->setId( 469976541 );

        $user2 = new UserMock();
        $user2->setId( 98756831 );

        $userSubtask1 = new UserSubtaskMock();
        $userSubtask1->setId( 600011 );
        $userSubtask1->setSubtask($subtask);
        $userSubtask1->setUser($user1);

        $userSubtask2 = new UserSubtaskMock();
        $userSubtask2->setId( 600021 );
        $userSubtask2->setSubtask($subtask);
        $userSubtask2->setUser($user2);

        $userSubtasks = new ArrayCollection();
        $userSubtasks->add($userSubtask1);
        $userSubtasks->add($userSubtask2);

        $this->assertEquals($userSubtasks, $subtask->setUserSubtasks( $userSubtasks )->getUserSubtasks());
    }

    private function setEmailAttr(Subtask &$subtask){
        $subtask->setId(99999);
        $subtask->setEmailSendTo("jefferson.martin@esuite.edu");
        $subtask->setEmailSubject("test subject");
        $subtask->setEmbeddedContent("Embedded Content");
    }

    public function testIdMain() {
        $subtask = new Subtask();
        $this->setEmailAttr($subtask);

        $this->assertEquals($subtask->getId(), 99999);
    }

    public function testEmailSendTo() {
        $subtask = new Subtask();
        $this->setEmailAttr($subtask);

        $this->assertEquals($subtask->getEmailSendTo(), "jefferson.martin@esuite.edu");
    }

    public function testEmailSubject() {
        $subtask = new Subtask();
        $this->setEmailAttr($subtask);

        $this->assertEquals($subtask->getEmailSubject(), "test subject");
    }

    public function testEmbeddedContent() {
        $subtask = new Subtask();
        $this->setEmailAttr($subtask);

        $this->assertEquals($subtask->getEmbeddedContent(), "Embedded Content");
    }
}
