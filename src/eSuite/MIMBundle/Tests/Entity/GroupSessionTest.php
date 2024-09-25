<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 04/04/17
 * Time: 11:00 AM
 */
namespace esuite\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\GroupSession;
use esuite\MIMBundle\Entity\Session;

use esuite\MIMBundle\Tests\Mock\GroupSessionAttachmentMock;
use esuite\MIMBundle\Tests\Mock\GroupSessionMock;
use esuite\MIMBundle\Tests\Mock\SessionMock;

class GroupSessionTest extends \PHPUnit\Framework\TestCase
{
    public function testSession()
    {
        $groupSession = new GroupSession();

        $session = new Session();
        $sessionDescription = "This is a test description";
        $session->setDescription($sessionDescription);

        $groupSession->setSession($session);

        $this->assertEquals($session, $groupSession->getSession());
    }

    public function testHandoutsPublished()
    {
        $groupSession = new GroupSession();

        $isPublished = true;

        $this->assertEquals($isPublished, $groupSession->setHandoutsPublished($isPublished)->getHandoutsPublished());
    }

    public function testHandoutsNotPublished()
    {
        $groupSession = new GroupSession();

        $isPublished = false;

        $this->assertEquals($isPublished, $groupSession->setHandoutsPublished($isPublished)->getHandoutsPublished());
    }

    /* Base */
    public function testCreated()
    {
        $groupSession = new GroupSession();

        $now = new \DateTime();

        $this->assertEquals($now, $groupSession->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $groupSession = new GroupSession();

        $now = new \DateTime();

        $this->assertEquals($now, $groupSession->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $groupSession = new GroupSession();

        $now = new \DateTime();

        $groupSession->setUpdated($now);
        $groupSession->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $groupSession->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $groupSession = new GroupSessionMock();

        $id = 98765345678;

        $this->assertEquals($id, $groupSession->setId($id)->getId());
    }

    public function testSessionId()
    {
        $groupSession = new GroupSession();

        $session = new SessionMock();
        $sessionDescription = "This is a test description";
        $session->setDescription($sessionDescription);

        $id = 98765345678;
        $session->setId($id);

        $groupSession->setSession($session);

        $this->assertEquals($id, $groupSession->getSessionId());
    }

    public function testGroupSessionAttachments()
    {
        $groupSession = new GroupSessionMock();
        $groupSession->setId( 456787654 );

        $groupSessionAttachment1 = new GroupSessionAttachmentMock();
        $groupSessionAttachment1->setId( 46997654 );
        $groupSessionAttachment1->setAttachmentType("file_document");

        $groupSessionAttachment2 = new GroupSessionAttachmentMock();
        $groupSessionAttachment2->setId( 9875683 );
        $groupSessionAttachment2->setAttachmentType("linked_document");

        $groupSessionAttachment3 = new GroupSessionAttachmentMock();
        $groupSessionAttachment3->setId( 12345762 );
        $groupSessionAttachment3->setAttachmentType("link");

        $groupSessionAttachment4 = new GroupSessionAttachmentMock();
        $groupSessionAttachment4->setId( 6876933 );
        $groupSessionAttachment4->setAttachmentType("video");

        $groupSessionAttachments = new ArrayCollection();
        $groupSessionAttachments->add($groupSessionAttachment1);
        $groupSessionAttachments->add($groupSessionAttachment2);
        $groupSessionAttachments->add($groupSessionAttachment3);
        $groupSessionAttachments->add($groupSessionAttachment4);

        $this->assertEquals($groupSessionAttachments->toArray(), $groupSession->setGroupSessionAttachments( $groupSessionAttachments )->getGroupSessionAttachments());
    }

    public function testGroupSessionObjects()
    {
        $groupSession = new GroupSessionMock();
        $groupSession->setId( 456787654 );

        $groupSessionAttachment1 = new GroupSessionAttachmentMock();
        $groupSessionAttachment1->setId( 46997654 );
        $groupSessionAttachment1->setAttachmentType("file_document");

        $groupSessionAttachment2 = new GroupSessionAttachmentMock();
        $groupSessionAttachment2->setId( 9875683 );
        $groupSessionAttachment2->setAttachmentType("linked_document");

        $groupSessionAttachment3 = new GroupSessionAttachmentMock();
        $groupSessionAttachment3->setId( 12345762 );
        $groupSessionAttachment3->setAttachmentType("link");

        $groupSessionAttachment4 = new GroupSessionAttachmentMock();
        $groupSessionAttachment4->setId( 6876933 );
        $groupSessionAttachment4->setAttachmentType("video");

        $groupSessionAttachments = new ArrayCollection();
        $groupSessionAttachments->add($groupSessionAttachment1);
        $groupSessionAttachments->add($groupSessionAttachment2);
        $groupSessionAttachments->add($groupSessionAttachment3);
        $groupSessionAttachments->add($groupSessionAttachment4);

        $this->assertEquals(
            [
                $groupSessionAttachment1->getId(),
                $groupSessionAttachment2->getId(),
                $groupSessionAttachment3->getId(),
                $groupSessionAttachment4->getId()
            ],
            $groupSession->setGroupSessionAttachments( $groupSessionAttachments )->getGroupSessionsObjects()
        );
    }
}
