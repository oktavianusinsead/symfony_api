<?php
/**
 * Created by PhpStorm.
 * User: esuite
 * Date: 1/3/17
 * Time: 1:38 PM
 */

namespace esuite\MIMBundle\Tests\Mock;

use Doctrine\Common\Collections\ArrayCollection;
use esuite\MIMBundle\Entity\Administrator;
use esuite\MIMBundle\Entity\Course;
use JetBrains\PhpStorm\ArrayShape;

class CourseMock extends Course
{
    /**
     * Set course subscriptions
     *
     * @param ArrayCollection $courseSubscriptions array of CourseSubscriptions items
     *
     * @return Course
     */
    public function setCourseSubscriptions( $courseSubscriptions ): Course
    {
        $this->courseSubscriptions = $courseSubscriptions;

        return $this;
    }

    public function setSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    public  function setActivities($activities)
    {
        $this->activities = $activities;
    }

    public  function setGroups($groups)
    {
        $this->groups = $groups;
    }

    public function setTasks($tasks)
    {
        $this->tasks = $tasks;
    }

    public function setAnnouncements($announcements)
    {
        $this->announcements = $announcements;
    }
}
