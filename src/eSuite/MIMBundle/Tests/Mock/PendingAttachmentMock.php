<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\PendingAttachment;

class PendingAttachmentMock extends PendingAttachment
{
    public function setId($id)
    {
        $this->id = $id;
    }
}