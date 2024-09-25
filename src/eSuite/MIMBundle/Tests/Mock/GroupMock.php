<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 1/3/17
 * Time: 1:38 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Group;

class GroupMock extends Group
{
    /**
     * Set group activity
     *
     * @param ArrayCollection $groupActivities array of GroupActivity items
     *
     * @return Group
     */
    public function setGroupActivities( $groupActivities ) {
        $this->group_activities = $groupActivities;

        return $this;
    }

    /**
     * Set group session
     *
     * @param ArrayCollection $groupSessions array of GroupSession items
     *
     * @return Group
     */
    public function setGroupSessions( $groupSessions ) {
        $this->group_sessions = $groupSessions;

        return $this;
    }
}
