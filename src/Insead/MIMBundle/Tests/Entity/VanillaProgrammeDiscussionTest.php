<?php

namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Tests\Mock\VanillaProgrammeDiscussionMock;
use PHPUnit\Framework\TestCase;

class VanillaProgrammeDiscussionTest extends TestCase
{
    public function testVanillaProgrammeDiscussion()
    {
        $vanillaProgrammeDiscussion = new VanillaProgrammeDiscussionMock();
        $arrayToTest = [
            ["setId","getId", 111],
            ["setProgramme","getProgramme", new Programme()],
            ["setVanillaDiscussionId","getVanillaDiscussionId", 12345],
            ["setName","getName", "this is a name"],
            ["setDescription","getDescription", "this is a description"],
            ["setClosed","getClosed", true],
            ["setGroupId","getGroupId", 12345],
            ["setUrl","getUrl", "http://1234.insead.com"],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $vanillaProgrammeDiscussion->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $vanillaProgrammeDiscussion->$getMethod());
        }
    }
}
