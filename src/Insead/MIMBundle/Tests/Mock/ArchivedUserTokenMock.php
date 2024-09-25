<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\ArchivedUserToken;

class ArchivedUserTokenMock extends ArchivedUserToken
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
