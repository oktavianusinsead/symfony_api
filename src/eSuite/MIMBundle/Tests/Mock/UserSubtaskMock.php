<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 12:03 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\UserSubtask;

class UserSubtaskMock extends UserSubtask
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return UserSubtask
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }
}
