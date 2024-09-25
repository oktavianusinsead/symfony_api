<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 02/04/17
 * Time: 12:03 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\Video;

use Insead\MIMBundle\Tests\Mock\VideoMock;
use Insead\MIMBundle\Tests\Mock\SessionMock;
use Insead\MIMBundle\Tests\Mock\UserDocumentMock;
use Insead\MIMBundle\Tests\Mock\UserFavouriteMock;

class VideoTest extends \PHPUnit\Framework\TestCase
{
    public function testAttachmentType()
    {
        $video = new Video();

        $attachmentType = "video"; // hardcoded value in entity, this is the expected value

        $this->assertEquals($attachmentType, $video->getAttachmentType());
    }

    public function testUrl()
    {
        $video = new Video();

        $url = "https://this.is.a.test.url.com";

        $this->assertEquals($url, $video->setUrl($url)->getUrl());
    }

    public function testDuration()
    {
        $video = new Video();

        $duration = "12395443";

        $this->assertEquals($duration, $video->setDuration($duration)->getDuration());
    }

    /* Base */
    public function testCreated()
    {
        $video = new Video();

        $now = new \DateTime();

        $this->assertEquals($now, $video->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $video = new Video();

        $now = new \DateTime();

        $this->assertEquals($now, $video->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $video = new Video();

        $now = new \DateTime();

        $video->setUpdated($now);
        $video->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $video->getUpdated());
    }

    /* Attachment */
    public function testDocumentType()
    {
        $video = new Video();

        //0 - Required Reading
        //1 - Recommended Reading
        //2 - Handout

        $docType = "0";
        $this->assertEquals($docType, $video->setDocumentType($docType)->getDocumentType());

        $docType = "1";
        $this->assertEquals($docType, $video->setDocumentType($docType)->getDocumentType());

        $docType = "2";
        $this->assertEquals($docType, $video->setDocumentType($docType)->getDocumentType());
    }

    public function testPosition()
    {
        $video = new Video();

        $position = "4732";

        $this->assertEquals($position, $video->setPosition($position)->getPosition());
    }

    public function testPublishAt()
    {
        $video = new Video();

        $now = new \DateTime();

        $this->assertEquals($now, $video->setPublishAt($now)->getPublishAt());
    }

    public function testTitle()
    {
        $video = new Video();

        $title = "this is a test title for a Video";

        $this->assertEquals($title, $video->setTitle($title)->getTitle());
    }

    public function testDescription()
    {
        $video = new Video();

        $description = "this is a test description for a Video";

        $this->assertEquals($description, $video->setDescription($description)->getDescription());
    }

    public function testDueDate()
    {
        $video = new Video();

        $now = new \DateTime();

        $this->assertEquals($now, $video->setDueDate($now)->getDueDate());
    }

    /* Mocks */
    public function testId()
    {
        $video = new VideoMock();

        $id = 98765345678;

        $this->assertEquals($id, $video->setId($id)->getId());
    }

    public function testSession()
    {
        $video = new VideoMock();

        $session = new SessionMock();

        $sessionName = "This is a test session name for a Video";
        $session->setName($sessionName);

        $id = 98765345678;
        $video->setId($id);

        $video->setSession($session);

        $this->assertEquals($session, $video->getSession());
    }

    public function testSessionId()
    {
        $video = new VideoMock();

        $session = new SessionMock();

        $sessionName = "This is a test session name for a Video";
        $sessionId = "13654756";

        $session->setName($sessionName);
        $session->setId($sessionId);

        $id = 98765345678;
        $video->setId($id);

        $video->setSession($session);

        $this->assertEquals($sessionId, $video->getSessionId());
    }

    public function testUserDocument()
    {
        $video = new VideoMock();

        $userDocument1 = new UserDocumentMock();
        $userDocument1->setId(6389169);

        $userDocument2 = new UserDocumentMock();
        $userDocument2->setId(749020764);

        $userDocuments = new ArrayCollection();
        $userDocuments->add($userDocument1);
        $userDocuments->add($userDocument2);

        $this->assertEquals($userDocuments, $video->setUserDocuments($userDocuments)->getUserDocuments());
    }

    public function testUserFavourite()
    {
        $video = new VideoMock();

        $userFavourite1 = new UserFavouriteMock();
        $userFavourite1->setId(6389169);

        $userFavourite2 = new UserFavouriteMock();
        $userFavourite2->setId(749020764);

        $userFavourites = new ArrayCollection();
        $userFavourites->add($userFavourite1);
        $userFavourites->add($userFavourite2);

        $this->assertEquals($userFavourites, $video->setUserFavourites($userFavourites)->getUserFavourites());
    }
}
