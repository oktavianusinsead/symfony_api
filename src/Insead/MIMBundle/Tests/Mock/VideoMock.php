<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 03/04/17
 * Time: 3:02 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\Video;

class VideoMock extends Video
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return Video
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
     * @return Video
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
     * @return Video
     */
    public function setUserFavourites($userFavourites)
    {
        $this->userFavourites = $userFavourites;

        return $this;
    }
}
