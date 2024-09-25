<?php

namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Entity\Session;
use Insead\MIMBundle\Tests\Mock\OrganizationMock;
use Insead\MIMBundle\Tests\Mock\PendingAttachmentMock;
use PHPUnit\Framework\TestCase;

class PendingAttachmentTest extends TestCase
{
    public function testPendingAttachment()
    {
        $session = new Session();
        $session->setName("test session");
        $session->setId(12345);

        $pendingAttachment = new PendingAttachmentMock();
        $arrayToTest = [
            ["setId","getId", 111],
            ["setSession","getSession", $session],
            [null,"getSessionId", $session->getId()],
            ["setAttachmentId","getAttachmentId", 12345],
            ["setAttachmentType","getAttachmentType", "SomeType"],
            ["setPublishAt","getPublishAt", new \DateTime()],
            ["setPublished","getPublished", true],
        ];

        foreach($arrayToTest as $test){
            $setMethod = $test[0];
            $getMethod = $test[1];
            $valueToTest = $test[2];
            if ($setMethod) $pendingAttachment->$setMethod($valueToTest);
            if ($getMethod) $this->assertEquals($valueToTest, $pendingAttachment->$getMethod());
        }
    }
}
