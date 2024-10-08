<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 04/04/17
 * Time: 03:42 PM
 */
namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\UserFavourite;

use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\LinkMock;
use esuite\MIMBundle\Tests\Mock\UserFavouriteMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use esuite\MIMBundle\Tests\Mock\FileDocumentMock;
use esuite\MIMBundle\Tests\Mock\LinkedDocumentMock;
use esuite\MIMBundle\Tests\Mock\VideoMock;

class UserFavouriteTest extends \PHPUnit\Framework\TestCase
{
    /* Mocks */
    public function testId()
    {
        $userFavourite = new UserFavouriteMock();

        $id = 98765345678;

        $this->assertEquals($id, $userFavourite->setId($id)->getId());
    }

    public function testCourse()
    {
        $course = new CourseMock();
        $course->setId(637028648);

        $userFavourite = new UserFavourite();

        $this->assertEquals($course, $userFavourite->setCourse($course)->getCourse());
    }

    public function testUser()
    {
        $user = new UserMock();
        $user->setId(637028648);

        $userFavourite = new UserFavourite();

        $this->assertEquals($user, $userFavourite->setUser($user)->getUser());
    }

    public function testFileDocument()
    {
        $fileDocument = new FileDocumentMock();
        $fileDocument->setId(637028648);

        $userFavourite = new UserFavourite();

        $this->assertEquals($fileDocument, $userFavourite->setFileDocument($fileDocument)->getFileDocument());
    }

    public function testLinkedDocument()
    {
        $linkedDocument = new LinkedDocumentMock();
        $linkedDocument->setId(637028648);

        $userFavourite = new UserFavourite();

        $this->assertEquals($linkedDocument, $userFavourite->setLinkDocument($linkedDocument)->getLinkDocument());
    }

    public function testLink()
    {
        $link = new LinkMock();
        $link->setId(637028648);

        $userFavourite = new UserFavourite();

        $this->assertEquals($link, $userFavourite->setLink($link)->getLink());
    }

    public function testVideo()
    {
        $video = new VideoMock();
        $video->setId(637028648);

        $userFavourite = new UserFavourite();

        $this->assertEquals($video, $userFavourite->setVideo($video)->getVideo());
    }

    public function testDocumentType()
    {
        $userFavourite = new UserFavourite();

        //0 - Required Reading
        //1 - Recommended Reading
        //2 - Handout

        $docType = "0";
        $this->assertEquals($docType, $userFavourite->setDocumentType($docType)->getDocumentType());

        $docType = "1";
        $this->assertEquals($docType, $userFavourite->setDocumentType($docType)->getDocumentType());

        $docType = "2";
        $this->assertEquals($docType, $userFavourite->setDocumentType($docType)->getDocumentType());
    }
}
