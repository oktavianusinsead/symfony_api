<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 03:40 PM
 */
namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\UserToken;

use Insead\MIMBundle\Tests\Mock\UserMock;
use Insead\MIMBundle\Tests\Mock\UserTokenMock;

class UserTokenTest extends \PHPUnit\Framework\TestCase
{
    public function testAccessToken()
    {
        $userToken = new UserToken();

        $accessToken = "this is a test access token for a user token";

        $this->assertEquals($accessToken, $userToken->setAccessToken($accessToken)->getAccessToken());
    }

    public function testRefreshToken()
    {
        $userToken = new UserToken();

        $refreshToken = "this is a test refresh token for a user token";

        $this->assertEquals($refreshToken, $userToken->setRefreshToken($refreshToken)->getRefreshToken());
    }

    public function testOauthAccessToken()
    {
        $userToken = new UserToken();

        $oauthAccessToken = "this is a test oauth access token for a user token";

        $this->assertEquals($oauthAccessToken, $userToken->setOauthAccessToken($oauthAccessToken)->getOauthAccessToken());
    }

    public function testTokenExpiry()
    {
        $userToken = new UserToken();

        $now = new \DateTime();

        $this->assertEquals($now, $userToken->setTokenExpiry($now)->getTokenExpiry());
    }

    public function testScope()
    {
        $userToken = new UserToken();

        $scope = "this is a test scope for a user token";

        $this->assertEquals($scope, $userToken->setScope($scope)->getScope());
    }

    /* Base */
    public function testCreated()
    {
        $userToken = new UserToken();

        $now = new \DateTime();

        $this->assertEquals($now, $userToken->setCreated($now)->getCreated());
    }

    public function testUpdated()
    {
        $userToken = new UserToken();

        $now = new \DateTime();

        $this->assertEquals($now, $userToken->setUpdated($now)->getUpdated());
    }

    public function testUpdatedValue()
    {
        $userToken = new UserToken();

        $now = new \DateTime();

        $userToken->setUpdated($now);
        $userToken->setUpdatedValue();

        $this->assertGreaterThanOrEqual($now, $userToken->getUpdated());
    }

    /* Mocks */
    public function testId()
    {
        $userToken = new UserTokenMock();

        $id = 98765345678;

        $this->assertEquals($id, $userToken->setId($id)->getId());
    }

    public function testUser()
    {
        $userToken = new UserTokenMock();

        $user = new UserMock();
        $user->setBoxEmail("test.user@insead.edu");

        $this->assertEquals($user, $userToken->setUser($user)->getUser());
    }

    public function testSessionIndex() {
        $userToken = new UserTokenMock();
        $userToken->setSessionIndex("123456789");
        $this->assertEquals("123456789", $userToken->getSessionIndex());
    }
}
