<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\VanillaConversation;

class VanillaConversationMock extends VanillaConversation
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
