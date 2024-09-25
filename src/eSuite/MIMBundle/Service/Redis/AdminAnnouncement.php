<?php

namespace esuite\MIMBundle\Service\Redis;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AdminAnnouncement extends Base
{
    public function __construct( ParameterBagInterface $parameterBag, LoggerInterface $logger)
    {
        parent::__construct($parameterBag,$logger);
    }

    /**
     * Function for retrieving Announcement Message
     *
     * @return String
     */
    public function getAdminAnnouncementMessage() {
        $this->log("RETRIEVING SuperAdmin Announcement Message");
        $value = $this->redis->get("admin_announcement_message");
        return ($value ?: null);
    }

    /**
     * Function for retrieving Announcement Title
     *
     * @return String
     */
    public function getAdminAnnouncementTitle() {
        $this->log("RETRIEVING SuperAdmin Announcement Title");
        $value = $this->redis->get("admin_announcement_title");
        return ($value ?: null);
    }

    /**
     * Function for retrieving Announcement Type
     *
     * @return String
     */
    public function getAdminAnnouncementType() {
        $this->log("RETRIEVING SuperAdmin Announcement Type");
        $value = $this->redis->get("admin_announcement_type");
        return ($value ?: null);
    }

    /**
     * Function for saving Announcement Message
     *
     * @param $value
     * @return String
     */
    public function updateAdminAnnouncementMessage($value) {
        $this->log("Saving Admin Announcement Message");

        $newValue = '';
        if( $this->redis->set("admin_announcement_message",$value) ) {
            $newValue = $this->redis->get("admin_announcement_message");
        }

        return $newValue;
    }

    /**
     * Function for retrieving Announcement Title
     *
     * @param $value
     * @return String
     */
    public function updateAdminAnnouncementTitle($value){
        $this->log("Saving Admin Announcement Title");

        $newValue = '';
        if( $this->redis->set("admin_announcement_title",$value) ) {
            $newValue = $this->redis->get("admin_announcement_title");
        }

        return $newValue;
    }

    /**
     * Function for retrieving Announcement Type
     *
     * @param $value
     * @return String
     */
    public function updateAdminAnnouncementType($value) {
        $this->log("Saving Admin Announcement Type");

        $newValue = '';
        if( $this->redis->set("admin_announcement_type",$value) ) {
            $newValue = $this->redis->get("admin_announcement_type");
        }

        return $newValue;
    }
}
