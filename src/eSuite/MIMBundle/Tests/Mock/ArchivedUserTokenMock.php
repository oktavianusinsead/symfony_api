<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\ArchivedUserToken;

class ArchivedUserTokenMock extends ArchivedUserToken
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
