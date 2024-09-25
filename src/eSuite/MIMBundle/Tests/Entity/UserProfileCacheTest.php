<?php

namespace esuite\MIMBundle\Tests\Entity;

use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\UserProfileCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\DateTime;

class UserProfileCacheTest extends TestCase
{
    public function testUserProfileCache()
    {
        $userProfileCache = new UserProfileCache();
        $arrayToTest = [
            ["setUser","getUser", new User()],
            ["setProfileUpdated","getProfileUpdated", true],
            ["setWorkExperienceStatus","getWorkExperienceStatus", "status"],
            ["setLastESBDteProcessed","getLastESBDteProcessed", new DateTime()],
            ["setLastUserDateUpdated","getLastUserDateUpdated", new DateTime()],
            ["setUpdatedFields","getUpdatedFields", ['firstname', 'lastname']],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $userProfileCache->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $userProfileCache->$getMethod());
        }
    }
}
