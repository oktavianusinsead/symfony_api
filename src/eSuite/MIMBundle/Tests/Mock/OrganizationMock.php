<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\Organization;

class OrganizationMock extends Organization
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
