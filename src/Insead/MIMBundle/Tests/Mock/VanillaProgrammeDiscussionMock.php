<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\VanillaProgrammeDiscussion;

class VanillaProgrammeDiscussionMock extends VanillaProgrammeDiscussion
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
