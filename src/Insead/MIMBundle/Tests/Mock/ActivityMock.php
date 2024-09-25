<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 1/3/17
 * Time: 1:38 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\Activity;

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
