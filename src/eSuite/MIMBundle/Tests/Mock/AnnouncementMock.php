<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 12:03 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Announcement;

class AnnouncementMock extends Announcement
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return Announcement
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set user announcements
     *
     * @param ArrayCollection $userAnnouncements array of UserAnnouncement items
     *
     * @return Announcement
     */
    public function setUserAnnouncements( $userAnnouncements ) {
        $this->userAnnouncements = $userAnnouncements;

        return $this;
    }
}
