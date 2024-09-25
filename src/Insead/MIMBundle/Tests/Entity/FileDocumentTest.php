<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 02/04/17
 * Time: 12:04 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\FileDocument;

use Insead\MIMBundle\Tests\Mock\FileDocumentMock;
use Insead\MIMBundle\Tests\Mock\SessionMock;
use Insead\MIMBundle\Tests\Mock\UserDocumentMock;
use Insead\MIMBundle\Tests\Mock\UserFavouriteMock;
use PHPUnit\Framework\TestCase;

class FileDocumentTest extends TestCase
{
    public function testAttachmentType()
    {
        $fileDocument = new FileDocument();

        $attachmentType = "file_document"; // hardcoded value in entity, this is the expected value

        $this->assertEquals($attachmentType, $fileDocument->getAttachmentType());
    }

    public function testBoxId()
    {
        $fileDocument = new FileDocument();

        $boxId = "9854438026465";

        $this->assertEquals($boxId, $fileDocument->setBoxId($boxId)->getBoxId());
    }

    public function testPath()
    {
        $fileDocument = new FileDocument();

        $path = "/this/is/a/test/path";

        $this->assertEquals($path, $fileDocument->setPath($path)->getPath());
    }

    public function testMimeType()
    {
        $fileDocument = new FileDocument();

        $mimeType = "application/pdf";

        $this->assertEquals($mimeType, $fileDocument->setMimeType($mimeType)->getMimeType());
    }

    public function testContent()
    {
        $fileDocument = new FileDocument();

        $content = "this is a test content for file document";

        $this->assertEquals($content, $fileDocument->setContent($content)->getContent());
    }

    public function testFilename()
    {
        $fileDocument = new FileDocument();

        $filename = "this is a test filename for file document";

        $this->assertEquals($filename, $fileDocument->setFilename($filename)->getFilename());
    }

    public function testFilesize()
    {
        $fileDocument = new FileDocument();

        $filesize = "67482096402";

        $this->assertEquals($filesize, $fileDocument->setFilesize($filesize)->getFilesize());
    }

    public function testDuration()
    {
        $fileDocument = new FileDocument();

        $duration = "12395443";

        $this->assertEquals($duration, $fileDocument->setDuration($duration)->getDuration());
    }

    public function testPages()
    {
        $fileDocument = new FileDocument();

        $pages = "74936592";

        $this->assertEquals($pages, $fileDocument->setPages($pages)->getPages());
    }

    /* Base */
    public function testCreated()
    {
        $fileDocument = new FileDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $fileDocument->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $fileDocument = new FileDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $fileDocument->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $fileDocument = new FileDocument();

        $now = new \DateTime();

        $fileDocument->setUpdated($now);
        $fileDocument->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $fileDocument->getUpdated());
    }

    /* Attachment */
    public function testDocumentType()
    {
        $fileDocument = new FileDocument();

        //0 - Required Reading
        //1 - Recommended Reading
        //2 - Handout

        $docType = "0";
        $this->assertEquals($docType, $fileDocument->setDocumentType($docType)->getDocumentType());

        $docType = "1";
        $this->assertEquals($docType, $fileDocument->setDocumentType($docType)->getDocumentType());

        $docType = "2";
        $this->assertEquals($docType, $fileDocument->setDocumentType($docType)->getDocumentType());
    }

    public function testPosition()
    {
        $fileDocument = new FileDocument();

        $position = "4732";

        $this->assertEquals($position, $fileDocument->setPosition($position)->getPosition());
    }

    public function testPublishAt()
    {
        $fileDocument = new FileDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $fileDocument->setPublishAt($now)->getPublishAt());
    }

    public function testTitle()
    {
        $fileDocument = new FileDocument();

        $title = "this is a test title for a file document";

        $this->assertEquals($title, $fileDocument->setTitle($title)->getTitle());
    }

    public function testDescription()
    {
        $fileDocument = new FileDocument();

        $description = "this is a test description for a file document";

        $this->assertEquals($description, $fileDocument->setDescription($description)->getDescription());
    }

    public function testDueDate()
    {
        $fileDocument = new FileDocument();

        $now = new \DateTime();

        $this->assertEquals($now, $fileDocument->setDueDate($now)->getDueDate());
    }

    /* Mocks */
    public function testId()
    {
        $fileDocument = new FileDocumentMock();

        $id = 98765345678;

        $this->assertEquals($id, $fileDocument->setId($id)->getId());
    }

    public function testSession()
    {
        $fileDocument = new FileDocumentMock();

        $session = new SessionMock();

        $sessionName = "This is a test session name for a File Document";
        $session->setName($sessionName);

        $id = 98765345678;
        $fileDocument->setId($id);

        $fileDocument->setSession($session);

        $this->assertEquals($session, $fileDocument->getSession());
    }

    public function testSessionId()
    {
        $fileDocument = new FileDocumentMock();

        $session = new SessionMock();

        $sessionName = "This is a test session name for a File Document";
        $sessionId = "13654756";

        $session->setName($sessionName);
        $session->setId($sessionId);

        $id = 98765345678;
        $fileDocument->setId($id);

        $fileDocument->setSession($session);

        $this->assertEquals($sessionId, $fileDocument->getSessionId());
    }

    public function testUserDocument()
    {
        $fileDocument = new FileDocumentMock();

        $userDocument1 = new UserDocumentMock();
        $userDocument1->setId(6389169);

        $userDocument2 = new UserDocumentMock();
        $userDocument2->setId(749020764);

        $userDocuments = new ArrayCollection();
        $userDocuments->add($userDocument1);
        $userDocuments->add($userDocument2);

        $this->assertEquals($userDocuments, $fileDocument->setUserDocuments($userDocuments)->getUserDocuments());
    }

    public function testUserFavourite()
    {
        $fileDocument = new FileDocumentMock();

        $userFavourite1 = new UserFavouriteMock();
        $userFavourite1->setId(6389169);

        $userFavourite2 = new UserFavouriteMock();
        $userFavourite2->setId(749020764);

        $userFavourites = new ArrayCollection();
        $userFavourites->add($userFavourite1);
        $userFavourites->add($userFavourite2);

        $this->assertEquals($userFavourites, $fileDocument->setUserFavourites($userFavourites)->getUserFavourites());
    }

    public function testAWS() {
        $fileDocument = new FileDocument();

        $fileDocument->setUploadToS3(true);
        $this->assertEquals($fileDocument->getUploadToS3(), true);

        $fileDocument->setUploadToS3(false);
        $this->assertEquals($fileDocument->getUploadToS3(), false);

        $fileDocument->setAwsPath("/some/path");
        $this->assertEquals($fileDocument->getAwsPath(), "/some/path");

        $fileDocument->setFileId("fieldtest");
        $this->assertEquals($fileDocument->getFileId(), "fieldtest");
    }
}
