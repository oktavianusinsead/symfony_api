<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 03/04/17
 * Time: 3:02 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\LinkedDocument;

class LinkedDocumentMock extends LinkedDocument
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return LinkedDocument
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set user documents
     *
     * @param ArrayCollection $userDocuments array of UserDocument items
     *
     * @return LinkedDocument
     */
    public function setUserDocuments($userDocuments)
    {
        $this->userDocuments = $userDocuments;

        return $this;
    }

    /**
     * Set user favourites
     *
     * @param ArrayCollection $userFavourites array of UserFavourite items
     *
     * @return LinkedDocument
     */
    public function setUserFavourites($userFavourites)
    {
        $this->userFavourites = $userFavourites;

        return $this;
    }
}
