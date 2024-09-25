<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Tests\Mock\BarcoUserGroupMock;
use PHPUnit\Framework\TestCase;

class BarcoUserGroupTest extends TestCase
{
    public function testBarcoUser()
    {
        $barcoUser = new BarcoUserGroupMock();
        $arrayToTest = [
            ["setId","getId", 20],
            ["setGroupID","getGroupID", 12345],
            ["setGroupName","getGroupName", "COHORT 1"],
            ["setGroupDate","getGroupDate", new \DateTime()],
            ["setGroupCampus","getGroupCampus", "Singapore"],
            ["setGroupTerm","getGroupTerm", 1100],
            ["setGroupClassNbr","getGroupClassNbr", 3300],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            $barcoUser->$setMethod($valueToTest);
            $this->assertEquals($valueToTest, $barcoUser->$getMethod());
        }
    }
}