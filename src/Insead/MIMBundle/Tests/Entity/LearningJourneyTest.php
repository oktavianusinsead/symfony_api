<?php

namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Tests\Mock\CourseMock;
use Insead\MIMBundle\Tests\Mock\LearningJourneyMock;
use PHPUnit\Framework\TestCase;

class LearningJourneyTest extends TestCase
{
    public function testLearningJourney()
    {
        $course = new CourseMock();
        $course->setId(12);
        $course->setName("this is a test course for an activity");

        $learningJourney = new LearningJourneyMock();
        $arrayToTest = [
            ["setId","getId", 20],
            ["setCourse","getCourse", $course],
            ["setTitle","getTitle", "Learning Journey Title"],
            ["setDescription","getDescription", "Learning Journey Description"],
            ["setPublishedAt","getPublishedAt", new \DateTime()],
            ["setPublished","getPublished", true],
            [null,"getCourseId", $course->getId()],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $learningJourney->$setMethod($valueToTest);
            $this->assertEquals($valueToTest, $learningJourney->$getMethod());
        }
    }
}
