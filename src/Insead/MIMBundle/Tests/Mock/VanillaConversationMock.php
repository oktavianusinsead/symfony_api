<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\VanillaConversation;

class VanillaConversationMock extends VanillaConversation
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
