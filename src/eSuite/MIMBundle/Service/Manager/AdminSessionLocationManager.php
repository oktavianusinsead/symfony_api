<?php

namespace esuite\MIMBundle\Service\Manager;

use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

use esuite\MIMBundle\Entity\AdminSessionLocation;

use esuite\MIMBundle\Exception\ResourceNotFoundException;


class AdminSessionLocationManager extends Base
{
    /**
     * Function to retrieve an existing AdminSessionLocation
     *
     * @param Request       $request            Request Object
     * @param String        $courseId           id of the Course
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getAdminSessionLocation(Request $request, $courseId)
    {
        /** @var User $user */
        $user = $this->getCurrentUserObj($request);

        if(!$user) {
            $this->log('Invalid User');
            throw new ResourceNotFoundException('Current user was not found in the system');
        }

        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy( ['id' => $courseId] );

        if(!$course) {
            $this->log('Course not found:' . $courseId);
            throw new ResourceNotFoundException('Course was not found in the system');
        }

        $this->log("CourseId: ".$course->getId());

        $location = $this->entityManager
            ->getRepository(AdminSessionLocation::class)
            ->findOneBy( ['user' => $user, 'course' => $course] );

        if(!$location) {
            $location = new AdminSessionLocation();
            $location->setCourse($course);
            $location->setUser($user);
            $location->setLocation("");
        }

        return ["admin-session-locations" => ['location' => $location->getLocation(), 'id' => $course->getId()]];
    }

}
