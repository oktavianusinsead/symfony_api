<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 31/3/17
 * Time: 06:04 pM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\Link;

use Insead\MIMBundle\Tests\Mock\LinkMock;
use Insead\MIMBundle\Tests\Mock\SessionMock;
use Insead\MIMBundle\Tests\Mock\UserDocumentMock;
use Insead\MIMBundle\Tests\Mock\UserFavouriteMock;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    public function testAttachmentType()
    {
        $link = new Link();

        $attachmentType = "link"; // hardcoded value in entity, this is the expected value

        $this->assertEquals($attachmentType, $link->getAttachmentType());
    }

    public function testThumbnail()
    {
        $link = new Link();

        $thumbnail = "this is a test thumbnail for link";

        $this->assertEquals($thumbnail, $link->setThumbnail($thumbnail)->getThumbnail());
    }

    public function testUrl()
    {
        $link = new Link();

        $url = "https://this.is.a.test.url.com";

        $this->assertEquals($url, $link->setUrl($url)->getUrl());
    }

    /* Base */
    public function testCreated()
    {
        $link = new Link();

        $now = new \DateTime();

        $this->assertEquals($now, $link->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $link = new Link();

        $now = new \DateTime();

        $this->assertEquals($now, $link->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $link = new Link();

        $now = new \DateTime();

        $link->setUpdated($now);
        $link->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $link->getUpdated());
    }

    /* Attachment */
    public function testDocumentType()
    {
        $link = new Link();

        //0 - Required Reading
        //1 - Recommended Reading
        //2 - Handout

        $docType = "0";
        $this->assertEquals($docType, $link->setDocumentType($docType)->getDocumentType());

        $docType = "1";
        $this->assertEquals($docType, $link->setDocumentType($docType)->getDocumentType());

        $docType = "2";
        $this->assertEquals($docType, $link->setDocumentType($docType)->getDocumentType());
    }

    public function testPosition()
    {
        $link = new Link();

        $position = "4732";

        $this->assertEquals($position, $link->setPosition($position)->getPosition());
    }

    public function testPublishAt()
    {
        $link = new Link();

        $now = new \DateTime();

        $this->assertEquals($now, $link->setPublishAt($now)->getPublishAt());
    }

    public function testTitle()
    {
        $link = new Link();

        $title = "this is a test title for a Link";

        $this->assertEquals($title, $link->setTitle($title)->getTitle());
    }

    public function testDescription()
    {
        $link = new Link();

        $description = "this is a test description for a Link";

        $this->assertEquals($description, $link->setDescription($description)->getDescription());
    }

    public function testDueDate()
    {
        $link = new Link();

        $now = new \DateTime();

        $this->assertEquals($now, $link->setDueDate($now)->getDueDate());
    }

    /* Mocks */
    public function testId()
    {
        $link = new LinkMock();

        $id = 98765345678;

        $this->assertEquals($id, $link->setId($id)->getId());
    }

    public function testSession()
    {
        $link = new LinkMock();

        $session = new SessionMock();

        $sessionName = "This is a test session name for a Link";
        $session->setName($sessionName);

        $id = 98765345678;
        $link->setId($id);

        $link->setSession($session);

        $this->assertEquals($session, $link->getSession());
    }

    public function testSessionId()
    {
        $link = new LinkMock();

        $session = new SessionMock();

        $sessionName = "This is a test session name for a Link";
        $sessionId = "13654756";

        $session->setName($sessionName);
        $session->setId($sessionId);

        $id = 98765345678;
        $link->setId($id);

        $link->setSession($session);

        $this->assertEquals($sessionId, $link->getSessionId());
    }

    public function testUserDocument()
    {
        $link = new LinkMock();

        $userDocument1 = new UserDocumentMock();
        $userDocument1->setId(6389169);

        $userDocument2 = new UserDocumentMock();
        $userDocument2->setId(749020764);

        $userDocuments = new ArrayCollection();
        $userDocuments->add($userDocument1);
        $userDocuments->add($userDocument2);

        $this->assertEquals($userDocuments, $link->setUserDocuments($userDocuments)->getUserDocuments());
    }

    public function testUserFavourite()
    {
        $link = new LinkMock();

        $userFavourite1 = new UserFavouriteMock();
        $userFavourite1->setId(6389169);

        $userFavourite2 = new UserFavouriteMock();
        $userFavourite2->setId(749020764);

        $userFavourites = new ArrayCollection();
        $userFavourites->add($userFavourite1);
        $userFavourites->add($userFavourite2);

        $this->assertEquals($userFavourites, $link->setUserFavourites($userFavourites)->getUserFavourites());
    }
}
