<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 11:52 AM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\Subtask;

class SubtaskMock extends Subtask
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return Subtask
     */
    public function setId( $id = null ) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set user subtasks
     *
     * @param ArrayCollection $userSubtasks array of UserSubtasks items
     *
     * @return Subtask
     */
    public function setUserSubtasks( $userSubtasks ) {
        $this->userSubtasks = $userSubtasks;

        return $this;
    }
}
