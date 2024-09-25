<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\Organization;

class OrganizationMock extends Organization
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
