<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 11:52 AM
 */
namespace esuite\MIMBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\UserDevice;

use esuite\MIMBundle\Entity\UserProfileCache;
use esuite\MIMBundle\Tests\Mock\CourseSubscriptionMock;
use esuite\MIMBundle\Tests\Mock\GroupMock;
use esuite\MIMBundle\Tests\Mock\SessionMock;
use esuite\MIMBundle\Tests\Mock\SubtaskMock;
use esuite\MIMBundle\Tests\Mock\UserAnnouncementMock;
use esuite\MIMBundle\Tests\Mock\UserDeviceMock;
use esuite\MIMBundle\Tests\Mock\UserDocumentMock;
use esuite\MIMBundle\Tests\Mock\UserFavouriteMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
use esuite\MIMBundle\Tests\Mock\UserSubtaskMock;
use esuite\MIMBundle\Tests\Mock\UserTokenMock;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testPeopleSoftId()
    {
        $user = new User();

        $peoplesoftId = "05379321";

        $this->assertEquals($peoplesoftId, $user->setPeoplesoftId($peoplesoftId)->getPeoplesoftId());
    }

    public function testBoxId()
    {
        $user = new User();

        $boxId = "05379321";

        $this->assertEquals($boxId, $user->setBoxId($boxId)->getBoxId());
    }

    public function testBoxEmail()
    {
        $user = new User();

        $boxEmail = "this.is.a.sample@email.ad";

        $this->assertEquals($boxEmail, $user->setBoxEmail($boxEmail)->getBoxEmail());
    }

    public function testAgreed()
    {
        $user = new User();

        $hasAgreed = true;

        $this->assertEquals($hasAgreed, $user->setAgreement($hasAgreed)->getAgreement());
    }

    public function testNotAgreed()
    {
        $user = new User();

        $hasAgreed = false;

        $this->assertEquals($hasAgreed, $user->setAgreement($hasAgreed)->getAgreement());
    }

    /* Base */
    public function testCreated()
    {
        $user = new User();

        $now = new \DateTime();

        $this->assertEquals($now, $user->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $user = new User();

        $now = new \DateTime();

        $this->assertEquals($now, $user->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $user = new User();

        $now = new \DateTime();

        $user->setUpdated($now);
        $user->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $user->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $user = new UserMock();

        $id = 98765345678;

        $this->assertEquals($id, $user->setId($id)->getId());
    }

    public function testIosDeviceIdField()
    {
        $user = new UserMock();

        $id = 98765345678;

        $this->assertEquals($id, $user->setIosDeviceId($id)->getIosDeviceId());
    }

    public function testGetSet()
    {
        $user = new UserMock();
        $arrayToTest = [
            ["setMemberships","getMemberships", ["member1", "member2"]],
            ["setProgrammeAdministrators","getProgrammeAdministrators", ["member1", "member2"]],
            ["setName",null, "Jeff"],
            ["setAgreementDate","getAgreementDate", new \DateTime()],
            ["setLastLoginDate","getLastLoginDate", new \DateTime()],
            ["setVanillaUserId","getVanillaUserId", 12345],
            ["setFirstname","getFirstname", "Jefferson"],
            ["setLastname","getLastname", "Martin"],
            ["setVanillaUserRefresh","getVanillaUserRefresh", true],
            [null,"getPSoftConstituentType", [
                1   => 'Alumni',
                6   => 'Student',
                7   => 'Faculty',
                9   => 'Staff',
                12  => 'Affiliate',
                90  => 'esuite Coaches',
                93  => 'Participant',
                94  => 'Past Participant',
                98  => 'esuite Contractor',
                99  => 'esuite Client',
                100 => 'Exchanger Student'
            ]],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $user->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $user->$getMethod());
        }

        $userForCacheProfile = new User();
        $userForCacheProfile->setPeoplesoftId('0911497');
        $userForCacheProfile->setFirstname('Jefferson');
        $userForCacheProfile->setLastname('Martin');

        $userProfileForCache = new UserProfileCache();
        $userProfileForCache->setUser($userForCacheProfile);
        $userProfileForCache->setConstituentTypes('7,9');
        $userProfileForCache->setFirstname($userForCacheProfile->getFirstname());
        $userProfileForCache->setLastname($userForCacheProfile->getLastname());

        $user->setCacheProfile($userProfileForCache);
        $user->setName();
        $this->assertEquals("Jefferson", $user->getFirstname());
        $this->assertEquals("Martin", $user->getLastname());

        $this->assertEquals($userProfileForCache, $user->getUserProfileCache());

        $this->assertEquals([
          ['constituent_type'=>'Faculty'], ['constituent_type'=>'Staff'],
        ],$user->getUserConstituentTypeString());
        $this->assertFalse($user->getAllowedToUpdate());

        $userForCoreProfile = new User();
        $userForCoreProfile->setPeoplesoftId('0911497');
        $userForCoreProfile->setFirstname('Jefferson');
        $userForCoreProfile->setLastname('Martin');

        $userProfileForCore = new UserProfileCache();
        $userProfileForCore->setUser($userForCoreProfile);
        $userProfileForCore->setConstituentTypes('93, 98');

        $user->setCoreProfile($userProfileForCore);
        $this->assertEquals([
            ['constituent_type'=>'Participant'], ['constituent_type'=>'esuite Contractor'],
        ],$user->getUserConstituentTypeString());
        $this->assertEquals($userProfileForCore, $user->getUserProfileCore());

        $user->setCoreUserGroup();
        $this->assertEquals(new ArrayCollection(), $user->getProgrammeCoreGroup());

        $user->setCacheProfileNotAllowed();
        $this->assertFalse($user->getAllowedToUpdate());
    }

    public function testCourseSubscriptions()
    {
        $user = new UserMock();
        $user->setId( 4567876541 );

        $courseSubscription1 = new CourseSubscriptionMock();
        $courseSubscription1->setId( 600011 );
        $courseSubscription1->setUser($user);

        $courseSubscription2 = new CourseSubscriptionMock();
        $courseSubscription2->setId( 600021 );
        $courseSubscription2->setUser($user);

        $courseSubscriptions = new ArrayCollection();
        $courseSubscriptions->add($courseSubscription1);
        $courseSubscriptions->add($courseSubscription2);

        $this->assertEquals($courseSubscriptions, $user->setCourseSubscription( $courseSubscriptions )->getCourseSubscription());
    }

    public function testUserAnnouncements()
    {
        $user = new UserMock();
        $user->setId( 4567876541 );

        $userAnnouncement1 = new UserAnnouncementMock();
        $userAnnouncement1->setId( 600011 );
        $userAnnouncement1->setUser($user);

        $userAnnouncement2 = new UserDocumentMock();
        $userAnnouncement2->setId( 600021 );
        $userAnnouncement2->setUser($user);

        $userAnnouncements = new ArrayCollection();
        $userAnnouncements->add($userAnnouncement1);
        $userAnnouncements->add($userAnnouncement2);

        $this->assertEquals($userAnnouncements, $user->setUserAnnouncements( $userAnnouncements )->getUserAnnouncements());
    }

    public function testUserDocuments()
    {
        $user = new UserMock();
        $user->setId( 4567876541 );

        $userDocument1 = new UserDocumentMock();
        $userDocument1->setId( 600011 );
        $userDocument1->setUser($user);

        $userDocument2 = new UserDocumentMock();
        $userDocument2->setId( 600021 );
        $userDocument2->setUser($user);

        $userDocuments = new ArrayCollection();
        $userDocuments->add($userDocument1);
        $userDocuments->add($userDocument2);

        $this->assertEquals($userDocuments, $user->setUserDocuments( $userDocuments )->getUserDocuments());
    }

    public function testUserFavourites()
    {
        $user = new UserMock();
        $user->setId( 4567876541 );

        $userFavourite1 = new UserFavouriteMock();
        $userFavourite1->setId( 600011 );
        $userFavourite1->setUser($user);

        $userFavourite2 = new UserFavouriteMock();
        $userFavourite2->setId( 600021 );
        $userFavourite2->setUser($user);

        $userFavourites = new ArrayCollection();
        $userFavourites->add($userFavourite1);
        $userFavourites->add($userFavourite2);

        $this->assertEquals($userFavourites, $user->setUserFavourites( $userFavourites )->getUserFavourites());
    }

    public function testUserSubtasks()
    {

        $user = new UserMock();
        $user->setId( 4567876541 );

        $subtask1 = new SubtaskMock();
        $subtask1->setId( 469976541 );

        $subtask2 = new SubtaskMock();
        $subtask2->setId( 98756831 );

        $userSubtask1 = new UserSubtaskMock();
        $userSubtask1->setId( 600011 );
        $userSubtask1->setSubtask($subtask1);
        $userSubtask1->setUser($user);

        $userSubtask2 = new UserSubtaskMock();
        $userSubtask2->setId( 600021 );
        $userSubtask2->setSubtask($subtask2);
        $userSubtask2->setUser($user);

        $userSubtasks = new ArrayCollection();
        $userSubtasks->add($userSubtask1);
        $userSubtasks->add($userSubtask2);

        $this->assertEquals($userSubtasks, $user->setUserSubtasks( $userSubtasks )->getUserSubtasks());
    }

    //sections
    public function testUserGroups()
    {
        $user = new UserMock();

        $user1 = new UserMock();
        $user1->setId(6389169);

        $user2 = new UserMock();
        $user2->setId(749020764);

        $group1 = new GroupMock();
        $group1->setId(8472074);
        $group1->addUser($user1);
        $group1->addUser($user2);

        $group2 = new GroupMock();
        $group2->setId(23453745);
        $group2->addUser($user1);
        $group2->addUser($user2);

        $groups = new ArrayCollection();
        $groups->add($group1);
        $groups->add($group2);

        $this->assertEquals($groups, $user->setUserGroups($groups)->getUserGroups());
    }

    //professors
    public function testUserSessions()
    {
        $user = new UserMock();

        $user1 = new UserMock();
        $user1->setId(6389169);

        $user2 = new UserMock();
        $user2->setId(749020764);

        $session1 = new SessionMock();
        $session1->setId(8472074);
        $session1->addProfessor($user1);
        $session1->addProfessor($user2);

        $session2 = new SessionMock();
        $session2->setId(23453745);
        $session2->addProfessor($user1);
        $session2->addProfessor($user2);

        $sessions = new ArrayCollection();
        $sessions->add($session1);
        $sessions->add($session2);

        $this->assertEquals($sessions, $user->setUserSessions($sessions)->getUserSessions());
    }

    //ios device id
    public function testIosDeviceId()
    {
        $deviceId1 = "73740274702";
        $deviceId2 = "73902479203784";

        $user = new User();
        $user->setIosDeviceId($deviceId1);
        $user->setIosDeviceId($deviceId2);

        $userDevice1 = new UserDevice();
        $userDevice1->setIosDeviceId($deviceId1);
        $userDevice1->setUser($user);

        $userDevice2 = new UserDevice();
        $userDevice2->setIosDeviceId($deviceId2);
        $userDevice2->setUser($user);

        $listOfDevices = [];
        /** @var UserDevice $userDevice */
        foreach ($user->getUserDevices() as $userDevice) {
            $listOfDevices[] = $userDevice->getIosDeviceId();
        }
        $this->assertEquals(
            [
                $userDevice1->getIosDeviceId(),
                $userDevice2->getIosDeviceId()
            ],
            $listOfDevices
        );
    }

    public function testExistingIosDeviceId()
    {
        $deviceId1 = "73740274702";
        $deviceId2 = "73902479203784";

        $user = new User();
        $user->setIosDeviceId($deviceId1);
        $user->setIosDeviceId($deviceId2);

        //add device1 again
        $user->setIosDeviceId($deviceId1);

        $userDevice1 = new UserDevice();
        $userDevice1->setIosDeviceId($deviceId1);
        $userDevice1->setUser($user);

        $userDevice2 = new UserDevice();
        $userDevice2->setIosDeviceId($deviceId2);
        $userDevice2->setUser($user);

        $listOfDevices = [];
        /** @var UserDevice $userDevice */
        foreach ($user->getUserDevices() as $userDevice) {
            $listOfDevices[] = $userDevice->getIosDeviceId();
        }
        $this->assertEquals(
            [
                $userDevice1->getIosDeviceId(),
                $userDevice2->getIosDeviceId()
            ],
            $listOfDevices
        );
    }

    public function testRemoveIosDeviceId()
    {
        $deviceId1 = "73740274702";
        $deviceId2 = "73902479203784";

        $user = new User();
        $user->setIosDeviceId($deviceId1);
        $user->setIosDeviceId($deviceId2);

        //remove device1
        $user->unsetIosDeviceId($deviceId1);

        $userDevice1 = new UserDevice();
        $userDevice1->setIosDeviceId($deviceId1);
        $userDevice1->setUser($user);

        $userDevice2 = new UserDevice();
        $userDevice2->setIosDeviceId($deviceId2);
        $userDevice2->setUser($user);

        $listOfDevices = [];
        /** @var UserDevice $userDevice */
        foreach ($user->getUserDevices() as $userDevice) {
            $listOfDevices[] = $userDevice->getIosDeviceId();
        }

        $this->assertEquals(
            [
                $userDevice2->getIosDeviceId()
            ],
            $listOfDevices
        );
    }

    //devices
    public function testUserDevice()
    {
        $user = new User();

        $userDevice1 = new UserDeviceMock();
        $userDevice1->setId(73740274702);

        $userDevice2 = new UserDeviceMock();
        $userDevice2->setId(9474720278492);

        $user->addUserDevice($userDevice1);
        $user->addUserDevice($userDevice2);

        $this->assertEquals(
            [
                $userDevice1,
                $userDevice2
            ],
            $user->getUserDevices()
        );
    }

    public function testRemoveUserDevice()
    {
        $user = new User();

        $userDevice1 = new UserDeviceMock();
        $userDevice1->setId(73740274702);

        $userDevice2 = new UserDeviceMock();
        $userDevice2->setId(9474720278492);

        $user->addUserDevice($userDevice1);
        $user->addUserDevice($userDevice2);

        $user->removeUserDevice($userDevice1);

        $this->assertEquals(
            [
                "1" => $userDevice2
            ],
            $user->getUserDevices()
        );
    }

    //tokens
    public function testUserToken()
    {
        $user = new User();

        $userToken1 = new UserTokenMock();
        $userToken1->setId(73740274702);
        $userToken1->setOauthAccessToken("hahs63kdy73");

        $userToken2 = new UserTokenMock();
        $userToken2->setId(9474720278492);
        $userToken2->setOauthAccessToken("s8d8do3m8d");

        $userToken3 = new UserTokenMock();
        $userToken3->setId(57848028470374);
        $userToken3->setOauthAccessToken("lsjf7io3me489fj");

        $user->setUserToken($userToken1);
        $user->setUserToken($userToken2);
        $user->setUserToken($userToken3);

        $this->assertEquals(
            [
                $userToken1,
                $userToken2,
                $userToken3
            ],
            $user->getUserTokens()
        );
    }

    public function testRemoveUserToken()
    {
        $user = new User();

        $userToken1 = new UserTokenMock();
        $userToken1->setId(73740274702);
        $userToken1->setOauthAccessToken("hahs63kdy73");

        $userToken2 = new UserTokenMock();
        $userToken2->setId(9474720278492);
        $userToken2->setOauthAccessToken("s8d8do3m8d");

        $userToken3 = new UserTokenMock();
        $userToken3->setId(57848028470374);
        $userToken3->setOauthAccessToken("lsjf7io3me489fj");

        $user->setUserToken($userToken1);
        $user->setUserToken($userToken2);
        $user->setUserToken($userToken3);

        $user->removeUserOauthAccessToken("s8d8do3m8d");

        $this->assertEquals(
            [
                "0" => $userToken1,
                "2" => $userToken3
            ],
            $user->getUserTokens()
        );
    }

    public function testResetUserToken()
    {
        $user = new User();

        $userToken1 = new UserTokenMock();
        $userToken1->setId(73740274702);
        $userToken1->setOauthAccessToken("hahs63kdy73");

        $userToken2 = new UserTokenMock();
        $userToken2->setId(9474720278492);
        $userToken2->setOauthAccessToken("s8d8do3m8d");

        $userToken3 = new UserTokenMock();
        $userToken3->setId(57848028470374);
        $userToken3->setOauthAccessToken("lsjf7io3me489fj");

        $user->setUserToken($userToken1);
        $user->setUserToken($userToken2);
        $user->setUserToken($userToken3);

        $user->resetUserTokens();

        $this->assertEquals([],$user->getUserTokens());
    }
}
