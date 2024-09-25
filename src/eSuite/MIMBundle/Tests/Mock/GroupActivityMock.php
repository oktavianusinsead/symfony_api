<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 1/3/17
 * Time: 1:38 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\GroupActivity;

class GroupActivityMock extends GroupActivity
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return GroupActivity
     */
    public function setId( $id = null ) {
        $this->id = $id;

        return $this;
    }
}
