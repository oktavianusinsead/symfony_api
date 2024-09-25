<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 04/04/17
 * Time: 03:38 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\UserDocument;

class UserDocumentMock extends UserDocument
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return UserDocument
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }
}
