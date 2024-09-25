<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 12:03 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\AdminSessionLocation;

class AdminSessionLocationMock extends AdminSessionLocation
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
