<?php

namespace esuite\MIMBundle\Service\Manager;

use Symfony\Component\HttpFoundation\Request;

use esuite\MIMBundle\Entity\Course;

use esuite\MIMBundle\Exception\ResourceNotFoundException;

class CourseMaterialsManager extends Base
{
    /**
     * Handler function to get the Course information and turn it into a structure profile book JSON information
     *
     * @param      Request $request Expects Header parameters
     * @param      String $courseId Course Id
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getProfileBook(Request $request, $courseId)
    {
        $this->log("Gathering information for Course " . $courseId);

        $em = $this->entityManager;
        /** @var Course $course */
        $course = $em
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if (!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $dateRange = "";
        if ($course->getStartDate() && $course->getEndDate()) {
            $monthS = "";
            $yearS = "";
            $monthE = "";
            $yearE = "";

            if ($course->getStartDate()) {
                $monthS = $course->getStartDate()->format("M");
                $yearS = $course->getStartDate()->format("Y");
            }

            if ($course->getEndDate()) {
                $monthE = $course->getEndDate()->format("M");
                $yearE = $course->getEndDate()->format("Y");
            }

            if ($yearS == $yearE) {
                if( $monthS == $monthE ) {
                    $dateRange = $monthE . " " . $yearE;
                } else {
                    $dateRange = $monthS . " - " . $monthE . " " . $yearE;
                }
            } else {
                $dateRange = $monthS . " " . $yearS . " - " . $monthE . " " . $yearE;
            }
        }

        $people = [];
        foreach( $course->getAllUsers() as $role => $items ) {
            if( $role == "students" ) {
                $roleLabel = "Participants";
            } else if( $role == "professors" ) {
                $roleLabel = "Faculty";
            } else if( $role == "directors" ) {
                $roleLabel = "Programme Directors";
            } else if( $role == "coordinators" ) {
                $roleLabel = "Coordinators";
            } else if( $role == "advisors" ) {
                $roleLabel = "Advisors";
            } else if( $role == "managers" ) {
                $roleLabel = "Programme Management";
            } else if( $role == "contacts" ) {
                $roleLabel = "Programme Contacts";
            } else if( $role == "consultants" ) {
                $roleLabel = "Programme Consultants";
            } else if( $role == "guests" ) {
                $roleLabel = "Guests";
            } else if( $role == "hidden" ) {
                $roleLabel = "Hidden";
            } else {
                $roleLabel = $role;
            }

            if( count($items) ) {
                $profiles = [];

                foreach( $items as $psoftId ) {
                    $profile = ["peoplesoft_id" => $psoftId];

                    array_push($profiles,$profile);
                }

                $roleBlock = ["role" => $roleLabel, "profiles" => $profiles];

                array_push($people,$roleBlock);
            }
        }

        $responseObj = ["title" => $course->getName(), "date" => $dateRange, "people" => $people];

        return $responseObj;
    }
}
