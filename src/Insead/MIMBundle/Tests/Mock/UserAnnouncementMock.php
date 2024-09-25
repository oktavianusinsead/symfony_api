<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 12:03 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\UserAnnouncement;

class UserAnnouncementMock extends UserAnnouncement
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return UserAnnouncement
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }
}
