<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\UserProfile;
use PHPUnit\Framework\TestCase;

class UserProfileTest extends TestCase
{
    public function testUserProfile()
    {
        $userProfile = new UserProfile();
        $arrayToTest = [
            ["setUser","getUser", new User()],
            ["setPreferredJobTitle","getPreferredJobTitle", "thisis a title"],
            ["setHidePhone","getHidePhone", true],
            ["setHideEmail","getHideEmail", true],
            ["setHasAccess","getHasAccess", true],
            ["setId","getId", 111],
            ["setFirstname","getFirstname", "Jefferson"],
            ["setLastname","getLastname", "Martin"],
            ["setAvatar","getAvatar", "Avatar"],
            ["setBio","getBio", "This is a bio"],
            ["setJobTitle","getJobTitle", "Job Title"],
            ["setOrganizationId","getOrganizationId", 12345],
            ["setOrganizationTitle","getOrganizationTitle", "esuite"],
            ["setUpnEmail","getUpnEmail", "jeff.martin@esuite.edu"],
            ["setNationality","getNationality", "Filipino"],
            ["setConstituentTypes","getConstituentTypes", "Staff"],
            ["setCellPhonePrefix","getCellPhonePrefix", "65"],
            ["setCellPhone","getCellPhone", "13945"],
            ["setPersonalPhonePrefix","getPersonalPhonePrefix", "65"],
            ["setPersonalPhone","getPersonalPhone", "12345"],
            ["setWorkPhonePrefix","getWorkPhonePrefix", "65"],
            ["setWorkPhone","getWorkPhone", "12345"],
            ["setPreferredPhone","getPreferredPhone", 1],
            ["setPersonalEmail","getPersonalEmail", "jhef@aaa.com"],
            ["setWorkEmail","getWorkEmail", "jhef@google.com"],
            ["setPreferredEmail","getPreferredEmail", 1],
            ["setAddressLine1","getAddressLine1", "Line 1"],
            ["setAddressLine2","getAddressLine2", "Line 2"],
            ["setAddressLine3","getAddressLine3", "Line 3"],
            ["setState","getState", "state"],
            ["setPostalCode","getPostalCode", 12345],
            ["setCountry","getCountry", "Singapore"],
            ["setCountryCode","getCountryCode", "SG"],
            ["setCity","getCity", "Hougang"],
            ["setJobStartDate","getJobStartDate", new \DateTime()],
            ["setJobEndDate","getJobEndDate", new \DateTime()],
            ["setIndustry","getIndustry", "School"],
            ["setCurrentlyWorkingHere","getCurrentlyWorkingHere", true],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $userProfile->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $userProfile->$getMethod());
        }
    }
}
