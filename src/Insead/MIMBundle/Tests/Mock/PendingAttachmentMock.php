<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\PendingAttachment;

class PendingAttachmentMock extends PendingAttachment
{
    public function setId($id)
    {
        $this->id = $id;
    }
}