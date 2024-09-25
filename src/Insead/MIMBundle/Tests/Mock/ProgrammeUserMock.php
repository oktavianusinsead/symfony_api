<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 06/06/17
 * Time: 3:48 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\ProgrammeUser;

class ProgrammeUserMock extends ProgrammeUser
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return ProgrammeUser
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }
}
