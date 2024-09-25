<?php
/**
 * Created by PhpStorm.
 * User: INSEAD
 * Date: 30/3/17
 * Time: 12:03 PM
 */

namespace Insead\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Entity\UserProfileCache;

class UserMock extends User
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return User
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set ios_device_id
     *
     * @param integer $deviceId mock deviceId of the entity
     *
     * @return User
     */
    public function setIosDeviceId( $deviceId )
    {
        $this->ios_device_id = $deviceId;

        return $this;
    }

    /**
     * Set course subscription
     *
     * @param ArrayCollection $courseSubscriptions array of CourseSubscription items
     *
     * @return User
     */
    public function setCourseSubscription($courseSubscriptions)
    {
        $this->courseSubscription = $courseSubscriptions;

        return $this;
    }

    /**
     * Set user announcement
     *
     * @param ArrayCollection $userAnnouncements array of UserAnnouncements items
     *
     * @return User
     */
    public function setUserAnnouncements($userAnnouncements)
    {
        $this->userAnnouncements = $userAnnouncements;

        return $this;
    }

    /**
     * Set user documents
     *
     * @param ArrayCollection $userDocuments array of UserDocuments items
     *
     * @return User
     */
    public function setUserDocuments($userDocuments)
    {
        $this->userDocuments = $userDocuments;

        return $this;
    }

    /**
     * Set user favourites
     *
     * @param ArrayCollection $userFavourites array of UserFavourites items
     *
     * @return User
     */
    public function setUserFavourites($userFavourites)
    {
        $this->userFavourites = $userFavourites;

        return $this;
    }

    /**
     * Set user subtasks
     *
     * @param ArrayCollection $userSubtasks array of UserSubtasks items
     *
     * @return User
     */
    public function setUserSubtasks($userSubtasks)
    {
        $this->userSubtasks = $userSubtasks;

        return $this;
    }

    /**
     * Set user groups
     *
     * @param ArrayCollection $userGroups array of UserGroups items
     *
     * @return User
     */
    public function setUserGroups($userGroups)
    {
        $this->userGroups = $userGroups;

        return $this;
    }

    /**
     * Set user sessions
     *
     * @param ArrayCollection $userSessions array of UserSessions items
     *
     * @return User
     */
    public function setUserSessions($userSessions)
    {
        $this->userSessions = $userSessions;

        return $this;
    }

    public function setProgrammeAdministrators($programmeAdministrators)
    {
        $this->programmeAdmins = $programmeAdministrators;
    }

    public function setCacheProfile($userProfile)
    {
        $this->userProfileCache = $userProfile;
    }

    public function setCacheProfileNotAllowed()
    {
        $this->userProfileCache = null;
    }

    public function setCoreProfile($userProfile)
    {
        $this->userProfile = $userProfile;
    }

    public function setCoreUserGroup()
    {
        $user1 = new UserMock();
        $user1->setPeoplesoftId('1111');
        $user2 = new UserMock();
        $user2->setPeoplesoftId('2222');

        $programmeUser = new ProgrammeUserMock();
        $programmeUser->setUser($this);

        $programmeUser1 = new ProgrammeUserMock();
        $programmeUser1->setUser($user1);

        $programmeUser2 = new ProgrammeUserMock();
        $programmeUser2->setUser($user2);

        $programmeUsers = new ArrayCollection();
        $programmeUsers->add($programmeUser);
        $programmeUsers->add($programmeUser1);
        $programmeUsers->add($programmeUser2);

        $programme = new ProgrammeMock();
        $programme->setProgrammeCoreGroup($programmeUsers);
    }
}
