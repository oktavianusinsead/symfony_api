<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 04/04/17
 * Time: 11:07 AM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\GroupSessionAttachment;

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
