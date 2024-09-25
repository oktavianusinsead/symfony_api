<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 04/04/17
 * Time: 11:25 AM
 */
namespace esuite\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\GroupSessionAttachment;

use esuite\MIMBundle\Tests\Mock\GroupSessionAttachmentMock;
use esuite\MIMBundle\Tests\Mock\GroupSessionMock;
use esuite\MIMBundle\Tests\Mock\SessionMock;

class GroupSessionAttachmentTest extends \PHPUnit\Framework\TestCase
{
    public function testAttachmentType()
    {
        $groupSessionAttachment = new GroupSessionAttachment();

        $attachmentType = "file_document";
        $this->assertEquals($attachmentType, $groupSessionAttachment->setAttachmentType($attachmentType)->getAttachmentType());

        $attachmentType = "linked_document";
        $this->assertEquals($attachmentType, $groupSessionAttachment->setAttachmentType($attachmentType)->getAttachmentType());

        $attachmentType = "link";
        $this->assertEquals($attachmentType, $groupSessionAttachment->setAttachmentType($attachmentType)->getAttachmentType());

        $attachmentType = "video";
        $this->assertEquals($attachmentType, $groupSessionAttachment->setAttachmentType($attachmentType)->getAttachmentType());
    }

    public function testAttachmentId()
    {
        $groupSessionAttachment = new GroupSessionAttachment();

        $id = 98765345678;

        $this->assertEquals($id, $groupSessionAttachment->setAttachmentId($id)->getAttachmentId());
    }

    public function testPublishAt()
    {
        $groupSessionAttachment = new GroupSessionAttachment();

        $now = new \DateTime();

        $this->assertEquals($now, $groupSessionAttachment->setPublishAt($now)->getPublishAt());
    }

    /* Base */
    public function testCreated()
    {
        $groupSessionAttachment = new GroupSessionAttachment();

        $now = new \DateTime();

        $this->assertEquals($now, $groupSessionAttachment->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $groupSessionAttachment = new GroupSessionAttachment();

        $now = new \DateTime();

        $this->assertEquals($now, $groupSessionAttachment->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $groupSessionAttachment = new GroupSessionAttachment();

        $now = new \DateTime();

        $groupSessionAttachment->setUpdated($now);
        $groupSessionAttachment->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $groupSessionAttachment->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $groupSessionAttachment = new GroupSessionAttachmentMock();

        $id = 98765345678;

        $this->assertEquals($id, $groupSessionAttachment->setId($id)->getId());
    }

    public function testSession()
    {
        $session = new SessionMock();
        $session->setId( 638927502047 );

        $groupSessionAttachment = new GroupSessionAttachmentMock();
        $groupSessionAttachment->setId( 4567876541 );

        $this->assertEquals($session, $groupSessionAttachment->setSession( $session )->getSession());
    }

    public function testSessionId()
    {
        $sessionId = 638927502047;
        $session = new SessionMock();
        $session->setId( $sessionId );

        $groupSessionAttachment = new GroupSessionAttachmentMock();
        $groupSessionAttachment->setId( 4567876541 );

        $this->assertEquals($sessionId, $groupSessionAttachment->setSession( $session )->getSessionId());
    }

    public function testGroupSession()
    {
        $groupSession = new GroupSessionMock();
        $groupSession->setId( 638927502047 );

        $groupSessionAttachment = new GroupSessionAttachmentMock();
        $groupSessionAttachment->setId( 4567876541 );

        $this->assertEquals($groupSession, $groupSessionAttachment->setGroupSession( $groupSession )->getGroupSession());
    }

    public function testGroupSessionId()
    {
        $groupSessionId = 638927502047;
        $groupSession = new GroupSessionMock();
        $groupSession->setId( $groupSessionId );

        $groupSessionAttachment = new GroupSessionAttachmentMock();
        $groupSessionAttachment->setId( 4567876541 );

        $this->assertEquals($groupSessionId, $groupSessionAttachment->setGroupSession( $groupSession )->getGroupSessionId());
    }

}
