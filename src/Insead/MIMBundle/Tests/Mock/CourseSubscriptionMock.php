<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 1:38 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\CourseSubscription;

class CourseSubscriptionMock extends CourseSubscription
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return CourseSubscription
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }

}
