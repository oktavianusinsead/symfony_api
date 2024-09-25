<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 02/04/17
 * Time: 11:45 AM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\LinkedDocument;

use Insead\MIMBundle\Tests\Mock\LinkedDocumentMock;
use Insead\MIMBundle\Tests\Mock\SessionMock;
use Insead\MIMBundle\Tests\Mock\UserDocumentMock;
use Insead\MIMBundle\Tests\Mock\UserFavouriteMock;

class LinkedDocumentTest extends \PHPUnit\Framework\TestCase
{
    public function testAttachmentType()
    {
        $linkedDoc = new LinkedDocument();

        $attachmentType = "linked_document"; // hardcoded value in entity, this is the expected value

        $this->assertEquals($attachmentType, $linkedDoc->getAttachmentType());
    }

    public function testUrl()
    {
        $linkedDoc = new LinkedDocument();

        $url = "https://this.is.a.test.url.com";

        $this->assertEquals($url, $linkedDoc->setUrl($url)->getUrl());
    }

    public function testMimeType()
    {
        $linkedDoc = new LinkedDocument();

        $mimeType = "application/pdf";

        $this->assertEquals($mimeType, $linkedDoc->setMimeType($mimeType)->getMimeType());
    }

    public function testExpiry()
    {
        $linkedDoc = new LinkedDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $linkedDoc->setExpiry($now)->getExpiry());
    }

    /* Base */
    public function testCreated()
    {
        $linkedDoc = new LinkedDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $linkedDoc->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $linkedDoc = new LinkedDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $linkedDoc->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $linkedDoc = new LinkedDocument();

        $now = new \DateTime();

        $linkedDoc->setUpdated($now);
        $linkedDoc->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $linkedDoc->getUpdated());
    }

    /* Attachment */
    public function testDocumentType()
    {
        $linkedDoc = new LinkedDocument();

        //0 - Required Reading
        //1 - Recommended Reading
        //2 - Handout

        $docType = "0";
        $this->assertEquals($docType, $linkedDoc->setDocumentType($docType)->getDocumentType());

        $docType = "1";
        $this->assertEquals($docType, $linkedDoc->setDocumentType($docType)->getDocumentType());

        $docType = "2";
        $this->assertEquals($docType, $linkedDoc->setDocumentType($docType)->getDocumentType());
    }

    public function testPosition()
    {
        $linkedDoc = new LinkedDocument();

        $position = "4732";

        $this->assertEquals($position, $linkedDoc->setPosition($position)->getPosition());
    }

    public function testPublishAt()
    {
        $linkedDoc = new LinkedDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $linkedDoc->setPublishAt($now)->getPublishAt());
    }

    public function testTitle()
    {
        $linkedDoc = new LinkedDocument();

        $title = "this is a test title for a Linked Document";

        $this->assertEquals($title, $linkedDoc->setTitle($title)->getTitle());
    }

    public function testDescription()
    {
        $linkedDoc = new LinkedDocument();

        $description = "this is a test description for a Linked Document";

        $this->assertEquals($description, $linkedDoc->setDescription($description)->getDescription());
    }

    public function testDueDate()
    {
        $linkedDoc = new LinkedDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $linkedDoc->setDueDate($now)->getDueDate());
    }

    /* Mocks */
    public function testId()
    {
        $linkedDoc = new LinkedDocumentMock();

        $id = 98765345678;

        $this->assertEquals($id, $linkedDoc->setId($id)->getId());
    }

    public function testSession()
    {
        $linkedDoc = new LinkedDocumentMock();

        $session = new SessionMock();

        $sessionName = "This is a test session name for a Link Document";
        $session->setName($sessionName);

        $id = 98765345678;
        $linkedDoc->setId($id);

        $linkedDoc->setSession($session);

        $this->assertEquals($session, $linkedDoc->getSession());
    }

    public function testSessionId()
    {
        $linkedDoc = new LinkedDocumentMock();

        $session = new SessionMock();

        $sessionName = "This is a test session name for a Linked Document";
        $sessionId = "13654756";

        $session->setName($sessionName);
        $session->setId($sessionId);

        $id = 98765345678;
        $linkedDoc->setId($id);

        $linkedDoc->setSession($session);

        $this->assertEquals($sessionId, $linkedDoc->getSessionId());
    }

    public function testUserDocument()
    {
        $linkedDoc = new LinkedDocumentMock();

        $userDocument1 = new UserDocumentMock();
        $userDocument1->setId(6389169);

        $userDocument2 = new UserDocumentMock();
        $userDocument2->setId(749020764);

        $userDocuments = new ArrayCollection();
        $userDocuments->add($userDocument1);
        $userDocuments->add($userDocument2);

        $this->assertEquals($userDocuments, $linkedDoc->setUserDocuments($userDocuments)->getUserDocuments());
    }

    public function testUserFavourite()
    {
        $linkedDoc = new LinkedDocumentMock();

        $userFavourite1 = new UserFavouriteMock();
        $userFavourite1->setId(6389169);

        $userFavourite2 = new UserFavouriteMock();
        $userFavourite2->setId(749020764);

        $userFavourites = new ArrayCollection();
        $userFavourites->add($userFavourite1);
        $userFavourites->add($userFavourite2);

        $this->assertEquals($userFavourites, $linkedDoc->setUserFavourites($userFavourites)->getUserFavourites());
    }
}
