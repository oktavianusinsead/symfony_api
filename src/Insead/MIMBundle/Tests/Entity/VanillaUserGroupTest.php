<?php

namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Tests\Mock\VanillaUserGroupMock;
use PHPUnit\Framework\TestCase;

class VanillaUserGroupTest extends TestCase
{
    public function testVanillaProgrammeGroup()
    {
        $vanillaUserGroup = new VanillaUserGroupMock();
        $arrayToTest = [
            ["setId","getId", 111],
            ["setUser","getUser", new User()],
            ["setGroup","getGroup", new VanillaUserGroupMock()],
            ["setAdded","getAdded", true],
            ["setRemove","getRemove", true],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $vanillaUserGroup->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $vanillaUserGroup->$getMethod());
        }
    }
}
