<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Tests\Mock\ArchivedUserTokenMock;
use esuite\MIMBundle\Tests\Mock\UserMock;
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

        $valueToTest = "edotuser";
        $archivedUserToken->setScope($valueToTest);
        $this->assertEquals($valueToTest, $archivedUserToken->getScope());

        $valueToTest = true;
        $archivedUserToken->setRefreshable($valueToTest);
        $this->assertEquals($valueToTest, $archivedUserToken->getRefreshable());
    }
}
