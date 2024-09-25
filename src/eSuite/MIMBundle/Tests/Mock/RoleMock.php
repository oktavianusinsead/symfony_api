<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 11:52 AM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Role;

class RoleMock extends Role
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return Role
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set course subscription
     *
     * @param ArrayCollection $courseSubscriptions array of CourseSubscription items
     *
     * @return Role
     */
    public function setCourseSubscription( $courseSubscriptions ) {
        $this->courseSubscription = $courseSubscriptions;

        return $this;
    }
}
