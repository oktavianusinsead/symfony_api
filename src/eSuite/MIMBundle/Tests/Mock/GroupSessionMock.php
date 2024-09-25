<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 31/3/17
 * Time: 04:56 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\GroupSession;

class GroupSessionMock extends GroupSession
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return GroupSession
     */
    public function setId( $id = null ) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set group session attachments
     *
     * @param ArrayCollection $groupSessionAttachments array of GroupSessionAttachments items
     *
     * @return GroupSession
     */
    public function setGroupSessionAttachments( $groupSessionAttachments ) {
        $this->group_session_attachments = $groupSessionAttachments;

        return $this;
    }
}
