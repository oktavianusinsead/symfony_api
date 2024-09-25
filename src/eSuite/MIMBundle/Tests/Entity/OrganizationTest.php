<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Tests\Mock\OrganizationMock;
use PHPUnit\Framework\TestCase;

class OrganizationTest extends TestCase
{
    public function testOrganization()
    {
        $organization = new OrganizationMock();
        $arrayToTest = [
            ["setId","getId", 111],
            ["setTitle","getTitle", "this is a test name for a course"],
            ["setExtOrgId","getExtOrgId", "extOrg"],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $organization->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $organization->$getMethod());
        }
    }
}
