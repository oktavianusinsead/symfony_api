<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 1/3/17
 * Time: 1:38 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Activity;

class ActivityMock extends Activity
{
    /**
     * Set group activity
     *
     * @param ArrayCollection $groupActivities array of GroupActivity items
     *
     * @return Activity
     */
    public function setGroupActivities( $groupActivities ) {
        $this->group_activities = $groupActivities;

        return $this;
    }
}
