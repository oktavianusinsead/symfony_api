<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\VanillaProgrammeDiscussion;

class VanillaProgrammeDiscussionMock extends VanillaProgrammeDiscussion
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
