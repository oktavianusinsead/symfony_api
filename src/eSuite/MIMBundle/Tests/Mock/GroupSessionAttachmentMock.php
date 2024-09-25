<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 04/04/17
 * Time: 11:07 AM
 */

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\GroupSessionAttachment;

class GroupSessionAttachmentMock extends GroupSessionAttachment
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return GroupSessionAttachment
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }
}
