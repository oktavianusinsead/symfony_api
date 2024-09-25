<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 03/04/17
 * Time: 3:02 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Link;

class LinkMock extends Link
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return Link
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
     * @return Link
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
     * @return Link
     */
    public function setUserFavourites($userFavourites)
    {
        $this->userFavourites = $userFavourites;

        return $this;
    }
}
