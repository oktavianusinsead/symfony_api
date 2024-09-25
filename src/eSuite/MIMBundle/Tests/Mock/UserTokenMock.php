<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 12:03 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\UserToken;

class UserTokenMock extends UserToken
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return UserToken
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }
}
