<?php

namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Tests\Mock\BarcoUserMock;
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
            ["setINSEADLogin","getINSEADLogin", "jeff.mart@insead.edu"],
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
