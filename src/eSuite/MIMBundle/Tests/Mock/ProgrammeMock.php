<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 30/3/17
 * Time: 1:38 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\ProgrammeAdministrator;

class ProgrammeMock extends Programme
{
    /**
     * Set id
     *
     * @param integer $id mock id of the entity
     *
     * @return Programme
     */
    public function setId( $id = null ) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set courses
     *
     * @param ArrayCollection $courses array of Courses items
     *
     * @return Programme
     */
    public function setCourses( $courses ) {
        $this->courses = $courses;

        return $this;
    }

    /**
     * Set course subscriptions
     *
     * @param ArrayCollection $courseSubscriptions array of CourseSubscriptions items
     *
     * @return Programme
     */
    public function setCourseSubscriptions( $courseSubscriptions ) {
        $this->courseSubscriptions = $courseSubscriptions;

        return $this;
    }

    /**
     * Set Programme Core Group
     *
     * @param ArrayCollection $programmeUsers array of ProgrammeUser items
     *
     * @return Programme
     */
    public function setProgrammeCoreGroup( $programmeUsers )
    {
        $this->coreUserGroup = $programmeUsers;

        return $this;
    }

    /**
     * Set programmeAdmins
     */
    public function setProgrammeAdministrators(array $programmeAdministrators)
    {
        $this->programmeAdmins = $programmeAdministrators;
    }
}
