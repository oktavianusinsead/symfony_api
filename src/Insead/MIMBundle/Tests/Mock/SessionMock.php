<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 1/3/17
 * Time: 1:38 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\Session;

class SessionMock extends Session
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return Session
     */
    public function setId( $id = null ) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set group session
     *
     * @param ArrayCollection $groupSessions array of GroupSession items
     *
     * @return Session
     */
    public function setGroupSessions( $groupSessions ) {
        $this->group_sessions = $groupSessions;

        return $this;
    }

    /**
     * Set Video
     *
     * @param ArrayCollection $videos array of Video items
     *
     * @return Session
     */
    public function setVideos( $videos ) {
        $this->videos = $videos;

        return $this;
    }

    /**
     * Set LinkedDocument
     *
     * @param ArrayCollection $linkedDocuments array of LinkedDocument items
     *
     * @return Session
     */
    public function setLinkedDocuments( $linkedDocuments ) {
        $this->linkedDocuments = $linkedDocuments;

        return $this;
    }

    /**
     * Set Link
     *
     * @param ArrayCollection $links array of Link items
     *
     * @return Session
     */
    public function setLinks( $links ) {
        $this->links = $links;

        return $this;
    }

    /**
     * Set FileDocument
     *
     * @param ArrayCollection $fileDocuments array of FileDocument items
     *
     * @return Session
     */
    public function setFileDocuments( $fileDocuments ) {
        $this->fileDocuments = $fileDocuments;

        return $this;
    }

    /**
     * Set GroupSessionAttachment
     *
     * @param ArrayCollection $groupSessionAttachments array of GroupSessionAttachment items
     *
     * @return Session
     */
    public function setGroupSessionAttachments( $groupSessionAttachments ) {
        $this->group_sessions_attachments = $groupSessionAttachments;

        return $this;
    }

    public function setPendingAttachments($pending_attachments)
    {
        $this->pending_attachments = $pending_attachments;
    }
}
