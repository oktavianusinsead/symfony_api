<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Tests\Mock\BarcoUserMock;
use PHPUnit\Framework\TestCase;

class BarcoUserTest extends TestCase
{
    public function testBarcoUser()
    {
        $barcoUser = new BarcoUserMock();
        $arrayToTest = [
            ["setId","getId", 20],
            ["setBarcoUserId","getBarcoUserId", 12345],
            ["setFirstName","getFirstName", "Jefferson"],
            ["setLastName","getLastName", "Martin"],
            ["setDisplayName","getDisplayName", "Jeff"],
            ["setesuiteLogin","getesuiteLogin", "jeff.mart@esuite.edu"],
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
