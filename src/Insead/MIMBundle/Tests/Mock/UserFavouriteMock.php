<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 04/04/17
 * Time: 03:38 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\UserFavourite;

class UserFavouriteMock extends UserFavourite
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return UserFavourite
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }
}
