<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 04/04/17
 * Time: 03:42 PM
 */
namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\UserDocument;

use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\LinkMock;
use esuite\MIMBundle\Tests\Mock\UserDocumentMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use esuite\MIMBundle\Tests\Mock\FileDocumentMock;
use esuite\MIMBundle\Tests\Mock\LinkedDocumentMock;
use esuite\MIMBundle\Tests\Mock\VideoMock;

class UserDocumentTest extends \PHPUnit\Framework\TestCase
{
    /* Mocks */
    public function testId()
    {
        $userDocument = new UserDocumentMock();

        $id = 98765345678;

        $this->assertEquals($id, $userDocument->setId($id)->getId());
    }

    public function testCourse()
    {
        $course = new CourseMock();
        $course->setId(637028648);

        $userDocument = new UserDocument();

        $this->assertEquals($course, $userDocument->setCourse($course)->getCourse());
    }

    public function testUser()
    {
        $user = new UserMock();
        $user->setId(637028648);

        $userDocument = new UserDocument();

        $this->assertEquals($user, $userDocument->setUser($user)->getUser());
    }

    public function testFileDocument()
    {
        $fileDocument = new FileDocumentMock();
        $fileDocument->setId(637028648);

        $userDocument = new UserDocument();

        $this->assertEquals($fileDocument, $userDocument->setFileDocument($fileDocument)->getFileDocument());
    }

    public function testLinkedDocument()
    {
        $linkedDocument = new LinkedDocumentMock();
        $linkedDocument->setId(637028648);

        $userDocument = new UserDocument();

        $this->assertEquals($linkedDocument, $userDocument->setLinkDocument($linkedDocument)->getLinkDocument());
    }

    public function testLink()
    {
        $link = new LinkMock();
        $link->setId(637028648);

        $userDocument = new UserDocument();

        $this->assertEquals($link, $userDocument->setLink($link)->getLink());
    }

    public function testVideo()
    {
        $video = new VideoMock();
        $video->setId(637028648);

        $userDocument = new UserDocument();

        $this->assertEquals($video, $userDocument->setVideo($video)->getVideo());
    }

    public function testDocumentType()
    {
        $userDocument = new UserDocument();

        //0 - Required Reading
        //1 - Recommended Reading
        //2 - Handout

        $docType = "0";
        $this->assertEquals($docType, $userDocument->setDocumentType($docType)->getDocumentType());

        $docType = "1";
        $this->assertEquals($docType, $userDocument->setDocumentType($docType)->getDocumentType());

        $docType = "2";
        $this->assertEquals($docType, $userDocument->setDocumentType($docType)->getDocumentType());
    }
}
