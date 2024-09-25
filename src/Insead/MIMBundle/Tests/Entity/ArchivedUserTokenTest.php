<?php

namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Tests\Mock\ArchivedUserTokenMock;
use Insead\MIMBundle\Tests\Mock\UserMock;
use PHPUnit\Framework\TestCase;

class ArchivedUserTokenTest extends TestCase
{
    public function testArchivedUserToken()
    {
        $user = new UserMock();
        $user->setId( 46997654 );

        $archivedUserToken = new ArchivedUserTokenMock();
        $archivedUserToken->setUser($user);

        $valueToTest = 20;
        $archivedUserToken->setId($valueToTest);
        $this->assertEquals($valueToTest, $archivedUserToken->getId());

        $this->assertEquals($user, $archivedUserToken->getUser());

        $valueToTest = "studyuser";
        $archivedUserToken->setScope($valueToTest);
        $this->assertEquals($valueToTest, $archivedUserToken->getScope());

        $valueToTest = true;
        $archivedUserToken->setRefreshable($valueToTest);
        $this->assertEquals($valueToTest, $archivedUserToken->getRefreshable());
    }
}
