<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 04/04/17
 * Time: 11:57 AM
 */
namespace esuite\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Session;

use esuite\MIMBundle\Tests\Mock\CourseMock;
use esuite\MIMBundle\Tests\Mock\CourseSubscriptionMock;
use esuite\MIMBundle\Tests\Mock\FileDocumentMock;
use esuite\MIMBundle\Tests\Mock\GroupMock;
use esuite\MIMBundle\Tests\Mock\GroupSessionAttachmentMock;
use esuite\MIMBundle\Tests\Mock\GroupSessionMock;
use esuite\MIMBundle\Tests\Mock\LinkedDocumentMock;
use esuite\MIMBundle\Tests\Mock\LinkMock;
use esuite\MIMBundle\Tests\Mock\ProgrammeMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use esuite\MIMBundle\Tests\Mock\SessionMock;
use esuite\MIMBundle\Tests\Mock\VideoMock;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected ProgrammeMock $programme;
    protected CourseMock $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->programme = new ProgrammeMock();
        $this->programme->setId(999999);
        $this->programme->setOverriderReadonly(true);

        $this->course = new CourseMock();
        $this->course->setId( 748292 );
        $this->course->setPublished( true );
        $this->course->setProgramme($this->programme);
    }

    public function testUid()
    {
        $session = new Session();

        $uid = "S-asbdjajda-783472";

        $this->assertEquals($uid, $session->setUid($uid)->getUid());
    }

    public function testBoxFolderId()
    {
        $session = new Session();

        $boxFolderId = "4780237469274692";

        $this->assertEquals($boxFolderId, $session->setBoxFolderId($boxFolderId)->getBoxFolderId());
    }

    public function testName()
    {
        $session = new Session();

        $name = "this is a test name for a session";

        $this->assertEquals($name, $session->setName($name)->getName());
    }

    public function testDescription()
    {
        $session = new Session();

        $description = "this is a test description for a session";

        $this->assertEquals($description, $session->setDescription($description)->getDescription());
    }

    public function testAbbreviation()
    {
        $session = new Session();

        $abbreviation = "this is a test abbreviation for a session";

        $this->assertEquals($abbreviation, $session->setAbbreviation($abbreviation)->getAbbreviation());
    }

    public function testStartDate()
    {
        $session = new Session();

        $now = new \DateTime();

        $this->assertEquals($now, $session->setStartDate($now)->getStartDate());
    }

    public function testEndDate()
    {
        $session = new Session();

        $now = new \DateTime();

        $this->assertEquals($now, $session->setEndDate($now)->getEndDate());
    }

    public function testPosition()
    {
        $session = new Session();

        $position = "0";
        $this->assertEquals($position, $session->setPosition($position)->getPosition());

        $position = "1";
        $this->assertEquals($position, $session->setPosition($position)->getPosition());

        $position = "537";
        $this->assertEquals($position, $session->setPosition($position)->getPosition());

        $position = "999";
        $this->assertEquals($position, $session->setPosition($position)->getPosition());
    }

    public function testPublished()
    {
        $session = new Session();

        $isPublished = true;

        $this->assertEquals($isPublished, $session->setPublished($isPublished)->getPublished());
    }

    public function testNotPublished()
    {
        $session = new Session();

        $isPublished = false;

        $this->assertEquals($isPublished, $session->setPublished($isPublished)->getPublished());
    }

    public function testBoxFolderName()
    {
        $session = new Session();

        $now = new \DateTime();
        $abbreviation = "abbre";
        $country = "country";

        $course = new Course();
        $course->setCountry($country);

        $session->setAbbreviation($abbreviation);
        $session->setStartDate($now);
        $session->setCourse($course);

        $boxFolderName = "S-" . $abbreviation . "-" . $country . "-" . date_format( $now, 'Ymd' );

        $this->assertEquals($boxFolderName, $session->getBoxFolderName());
    }

    public function testSharedBoxFolderName()
    {
        $session = new Session();

        $now = new \DateTime();
        $abbreviation = "abbre";
        $country = "country";

        $course = new Course();
        $course->setCountry($country);

        $session->setAbbreviation($abbreviation);
        $session->setStartDate($now);
        $session->setCourse($course);

        $boxFolderName = "S-" . $abbreviation . "-" . $country . "-" . date_format( $now, 'Ymd' ) . "-S";

        $this->assertEquals($boxFolderName, $session->getBoxFolderName(true));
    }

    /* Base */
    public function testCreated()
    {
        $session = new Session();

        $now = new \DateTime();

        $this->assertEquals($now, $session->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $session = new Session();

        $now = new \DateTime();

        $this->assertEquals($now, $session->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $session = new Session();

        $now = new \DateTime();

        $session->setUpdated($now);
        $session->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $session->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $session = new SessionMock();

        $id = 98765345678;

        $this->assertEquals($id, $session->setId($id)->getId());
    }

    public function testCourse()
    {
        $session = new SessionMock();

        $course = new CourseMock();
        $course->setName("this is a test course for a session");

        $this->assertEquals($course, $session->setCourse($course)->getCourse());
    }

    public function testCourseId()
    {
        $session = new SessionMock();

        $course = new CourseMock();
        $course->setName("this is a test course for a session");
        $course->setId(4560923759020);

        $this->assertEquals($course->getId(), $session->setCourse($course)->getCourseId());
    }

    public function testGetSet()
    {
        $session = new SessionMock();
        $arrayToTest = [
            ["setLatestHandout","getLatestHandout", "latestHandout"],
            ["setAlternateSessionName","getAlternateSessionName", "get alternate"],
            ["setSessionColor","getSessionColor", "red"],
            ["setOptionalText","getOptionalText", "Optional text"],
            ["setSessionScheduled","getSessionScheduled", true],
            ["setRemarks","getRemarks", "Some Remarks"],
            ["setWebView",null, true],
            ["setSerializeOnlyPublishedAttachments",null, true],
            ["showHandouts",null, true],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $session->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $session->$getMethod());
        }
    }

    public function testGetAttachmentList()
    {
        $peoplesoftId = "0911497";
        $session = new SessionMock();
        $session->checkGroupSessionAttachmentsFor($peoplesoftId);

        $user = new UserMock();
        $user->setId( 12345 );
        $user->setPeoplesoftId( $peoplesoftId );

        $group = new GroupMock();
        $group->setId( 84820284 );
        $group->addUser( $user );

        $group->setCourse($this->course);

        $groupSession = new GroupSessionMock();
        $groupSession->setId( 6392974 );
        $groupSession->setGroup( $group );

        $groupSessions = new ArrayCollection();
        $groupSessions->add($groupSession);

        $session->setGroupSessions($groupSessions);

        $groupSessionAttachments = new ArrayCollection();

        for( $i=0; $i < 3; $i++ ) {
            $groupSessionAttachment = new GroupSessionAttachmentMock();
            $groupSessionAttachment->setId( random_int(1000,9999) );
            $groupSessionAttachment->setSession($session);
            $groupSessionAttachment->setAttachmentType("linked_document");
            $groupSessionAttachment->setAttachmentId(0);
            $groupSessionAttachment->setGroupSession($groupSession);
            $groupSessionAttachments->add($groupSessionAttachment);
        }

        $session->setGroupSessionAttachments( $groupSessionAttachments );

        $fileDocuments = new ArrayCollection();
        $linkedDocuments = new ArrayCollection();
        $links = new ArrayCollection();
        $videos = new ArrayCollection();

        //$i is document type; $i=2 is handouts
        $latest = (new \DateTime())->add(new \DateInterval('P6D'));
        for( $i=0; $i < 3; $i++ ) {
            $fileDocument = new FileDocumentMock();
            $fileDocument->setId( random_int(1000,9999) );
            $fileDocument->setDocumentType( $i );
            $fileDocument->setSession($session);
            $fileDocument->setUpdated(new \DateTime());
            $fileDocument->setPublishAt(new \DateTime());

            $linkedDocument = new LinkedDocumentMock();
            $linkedDocument->setId( $i );
            $linkedDocument->setDocumentType( $i );
            $linkedDocument->setSession($session);
            $linkedDocument->setUpdated(new \DateTime());
            $linkedDocument->setPublishAt($latest);

            $link = new LinkMock();
            $link->setId( random_int(1000,9999) );
            $link->setDocumentType( $i );
            $link->setSession($session);
            $link->setUpdated(new \DateTime());
            $link->setPublishAt(new \DateTime());

            $video = new VideoMock();
            $video->setId( random_int(1000,9999) );
            $video->setDocumentType( $i );
            $video->setSession($session);
            $video->setUpdated(new \DateTime());
            $video->setPublishAt(new \DateTime());

            $fileDocuments->add($fileDocument);
            $linkedDocuments->add($linkedDocument);
            $links->add($link);
            $videos->add($video);
        }

        $session->setFileDocuments( $fileDocuments );
        $session->setLinkedDocuments( $linkedDocuments );
        $session->setLinks( $links );
        $session->setVideos( $videos );

        $this->assertEquals(count($fileDocuments) + count($linkedDocuments) + count($links) + count($videos), count($session->getAttachmentList()));
        $session->setSerializeOnlyPublishedAttachments();
        $this->assertEquals(count($fileDocuments) + count($links) + count($videos) + 1, count($session->getAttachmentList()));

        $session->showHandouts(true);
        $this->assertEquals(count($fileDocuments) + count($links) + count($videos) + 2, count($session->getAttachmentList()));

        $groupSessionAttachment = new GroupSessionAttachmentMock();
        $groupSessionAttachment->setId( random_int(1000,9999) );
        $groupSessionAttachment->setSession($session);
        $groupSessionAttachment->setAttachmentType("linked_document");
        $groupSessionAttachment->setAttachmentId(0);
        $groupSessionAttachment->setGroupSession($groupSession);
        $groupSessionAttachment->setUpdated($latest);
        $session->setLatestHandout($groupSessionAttachment);
        $this->assertEquals($latest, $session->getLatestHandoutPublishAt());

        $session->setPendingAttachments($groupSessionAttachments);
        $this->assertEquals($groupSessionAttachments, $session->getPendingAttachments());
    }

    public function testClone()
    {
        $session = new SessionMock();
        $session->setAbbreviation("1701276098_AJHDKJHD");
        $tmpSession = clone $session;
        $tmpSession->setCreated($session->getCreated());
        $tmpSession->setUpdated($session->getUpdated());
        $tmpSession->setAbbreviation($session->getAbbreviation());
        $session->setBoxFolderId($tmpSession->getBoxFolderId());
        $this->assertEquals($session, $tmpSession);
    }

    public function testProfessor()
    {
        $session = new SessionMock();

        $user1 = new UserMock();
        $user1->setId(764892047);
        $user1->setPeoplesoftId(2427242);

        $user2 = new UserMock();
        $user2->setId(23436345734);
        $user2->setPeoplesoftId(2352236);

        $user3 = new UserMock();
        $user3->setId(43542747);
        $user3->setPeoplesoftId(5424462);

        $session->addProfessor($user1);
        $session->addProfessor($user2);
        $session->addProfessor($user3);

        $this->assertEquals(
            [
                $user1,
                $user2,
                $user3
            ],
            $session->getProfessors()
        );
    }

    public function testProfessorList()
    {
        $session = new SessionMock();

        $user1 = new UserMock();
        $user1->setId(764892047);
        $user1->setPeoplesoftId(2427242);

        $user2 = new UserMock();
        $user2->setId(23436345734);
        $user2->setPeoplesoftId(2352236);

        $user3 = new UserMock();
        $user3->setId(43542747);
        $user3->setPeoplesoftId(5424462);

        $session->addProfessor($user1);
        $session->addProfessor($user2);
        $session->addProfessor($user3);

        $this->assertEquals(
            [
                $user1->getPeoplesoftId(),
                $user2->getPeoplesoftId(),
                $user3->getPeoplesoftId()
            ],
            $session->getProfessorList()
        );
    }

    public function testRemoveProfessor()
    {
        $session = new SessionMock();

        $user1 = new UserMock();
        $user1->setId(764892047);
        $user1->setPeoplesoftId(2427242);

        $user2 = new UserMock();
        $user2->setId(23436345734);
        $user2->setPeoplesoftId(2352236);

        $user3 = new UserMock();
        $user3->setId(43542747);
        $user3->setPeoplesoftId(5424462);

        $session->addProfessor($user1);
        $session->addProfessor($user2);
        $session->addProfessor($user3);

        $session->removeProfessor($user2);

        $this->assertEquals(
            [
                "0"=>$user1,
                "2"=>$user3
            ],
            $session->getProfessors()
        );
    }

    public function testGroupSessions()
    {
        $session = new SessionMock();
        $session->serializeOnlyPublished( true );
        $session->doNotShowGroupSessions( false );

        $groupSession = new GroupSessionMock();
        $groupSession->setId( 6392974 );

        $groupSessions = new ArrayCollection();
        $groupSessions->add($groupSession);

        $this->assertEquals( $groupSessions, $session->setGroupSessions($groupSessions)->getGroupSessions() );
    }

    public function testGroupSessionIds()
    {
        $session = new SessionMock();
        $session->serializeOnlyPublished( true );
        $session->doNotShowGroupSessions( false );

        $groupSession = new GroupSessionMock();
        $groupSession->setId( 6392974 );

        $groupSessions = new ArrayCollection();
        $groupSessions->add($groupSession);

        $session->setGroupSessions($groupSessions);

        $this->assertEquals(
            [$groupSession->getId()],
            $session->getGroupSessionIds()
        );

        $session->setWebView(true);
        $this->assertEquals(
            [],
            $session->getGroupSessionIds()
        );
    }

    public function testGroupSessionIdsEmpty()
    {
        $session = new SessionMock();
        $session->doNotShowGroupSessions( true );

        $this->assertEquals([],$session->getGroupSessionIds());
    }

    public function testFindGroupSessionForUser()
    {
        $peoplesoftId = 747297674;

        $session = new SessionMock();
        $session->serializeOnlyPublished( true );
        $session->doNotShowGroupSessions( false );
        $session->checkGroupSessionAttachmentsFor( $peoplesoftId );

        $user = new UserMock();
        $user->setId( 8482034 );
        $user->setPeoplesoftId( $peoplesoftId );

        $group = new GroupMock();
        $group->setId( 84820284 );
        $group->addUser( $user );

        $group->setCourse($this->course);

        $groupSession = new GroupSessionMock();
        $groupSession->setId( 6392974 );
        $groupSession->setGroup( $group );

        $groupSessions = new ArrayCollection();
        $groupSessions->add($groupSession);

        $session->setGroupSessions($groupSessions);

        $this->assertEquals(
            [$groupSession->getId()],
            $session->findGroupSessionsForUser()
        );
    }

    public function testFileDocuments()
    {
        $session = new SessionMock();

        $fileDocuments = new ArrayCollection();

        //$i is document type; $i=2 is handouts
        for( $i=0; $i < 3; $i++ ) {
            $fileDocument = new FileDocumentMock();
            $fileDocument->setId( random_int(1000,9999) );
            $fileDocument->setDocumentType( $i );

            $fileDocuments->add($fileDocument);
        }

        $session->setFileDocuments( $fileDocuments );

        $this->assertEquals(
            $fileDocuments,
            $session->getFileDocuments()
        );
    }

    public function testAllHandouts()
    {
        $session = new SessionMock();

        $fileDocuments = new ArrayCollection();
        $linkedDocuments = new ArrayCollection();
        $links = new ArrayCollection();
        $videos = new ArrayCollection();

        //$i is document type; $i=2 is handouts
        for( $i=0; $i < 3; $i++ ) {
            $fileDocument = new FileDocumentMock();
            $fileDocument->setId( random_int(1000,9999) );
            $fileDocument->setDocumentType( $i );

            $linkedDocument = new LinkedDocumentMock();
            $linkedDocument->setId( random_int(1000,9999) );
            $linkedDocument->setDocumentType( $i );

            $link = new LinkMock();
            $link->setId( random_int(1000,9999) );
            $link->setDocumentType( $i );

            $video = new VideoMock();
            $video->setId( random_int(1000,9999) );
            $video->setDocumentType( $i );

            $fileDocuments->add($fileDocument);
            $linkedDocuments->add($linkedDocument);
            $links->add($link);
            $videos->add($video);
        }

        $session->setFileDocuments( $fileDocuments );
        $session->setLinkedDocuments( $linkedDocuments );
        $session->setLinks( $links );
        $session->setVideos( $videos );

        $this->assertEquals(
            [
                $videos->get(2),
                $linkedDocuments->get(2),
                $links->get(2),
                $fileDocuments->get(2),
            ],
            $session->getAllHandouts()
        );
    }

    public function testGroupSessionAttachments()
    {
        $session = new SessionMock();

        $groupSessionAttachments = new ArrayCollection();

        for( $i=0; $i < 3; $i++ ) {
            $groupSessionAttachment = new GroupSessionAttachmentMock();
            $groupSessionAttachment->setId( random_int(1000,9999) );

            $groupSessionAttachments->add($groupSessionAttachment);
        }

        $session->setGroupSessionAttachments( $groupSessionAttachments );

        $this->assertEquals(
            $groupSessionAttachments,
            $session->getGroupSessionAttachments()
        );
    }
}
