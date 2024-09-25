<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Tests\Mock\VanillaProgrammeGroupMock;
use PHPUnit\Framework\TestCase;

class VanillaProgrammeGroupTest extends TestCase
{
    public function testVanillaProgrammeGroup()
    {
        $vanillaProgrammeGroup = new VanillaProgrammeGroupMock();
        $arrayToTest = [
            ["setId","getId", 111],
            ["setProgramme","getProgramme", new Programme()],
            ["setVanillaGroupName","getVanillaGroupName", "this is a name"],
            ["setVanillaGroupDescription","getVanillaGroupDescription", "this is a description"],
            ["setVanillaGroupId","getVanillaGroupId", 12345],
            ["setvanillaUserGroup","getvanillaUserGroup", "group"],
            ["setCourse","getCourse", new Course()],
            ["setInitial","getInitial", "12345"],
            ["setCourse","getCourse", []],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $vanillaProgrammeGroup->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $vanillaProgrammeGroup->$getMethod());
        }
    }
}
