<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Tests\Mock\VanillaConversationMock;
use PHPUnit\Framework\TestCase;

class VanillaConversationTest extends TestCase
{
    public function testVanillaConversation()
    {
        $vanillaConversation = new VanillaConversationMock();
        $arrayToTest = [
            ["setId","getId", 111],
            ["setProgramme","getProgramme", new Programme()],
            ["setUser","getUser", new User()],
            ["setUserList","getUserList", "this is a list"],
            ["setConversationID","getConversationID", 2222],
            ["setProcessed","processed", true],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $vanillaConversation->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $vanillaConversation->$getMethod());
        }
    }
}
