<?php

namespace Insead\MIMBundle\Service\Manager;

use Insead\MIMBundle\Entity\CourseSubscription;
use Insead\MIMBundle\Entity\Programme;
use Insead\MIMBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\StudyNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use Insead\MIMBundle\Service\BoxManager\BoxManager;
use Insead\MIMBundle\Service\StudyCourseBackup as Backup;

use Insead\MIMBundle\Entity\Activity;
use Insead\MIMBundle\Entity\Course;

use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CompanionManager extends Base
{
    /**
     * Function to retrieve all programmes
     *
     * @param $peoplesoft_id
     *
     * @return array
     */
    public function programmes($peoplesoft_id)
    {
        /** @var User $user */
        $user = $this->getUserObject($peoplesoft_id);
        if (!$user){
            return [];
        }
        
        $result = [];
        /** @var CourseSubscription $CSObject */
        foreach($user->getCourseSubscription() as $CSObject){
            $programme = $CSObject->getProgramme();
            if (!array_search($programme->getId(), array_column($result, 'id'))) {
                array_push($result, [
                    "id"         => $programme->getId(),
                    "title"      => $programme->getName(),
                    "start_date" => $programme->getStartDate(),
                    "end_date"   => $programme->getEndDate(),
                    "url"        => "/app/".$programme->getId()."/home"
                ]);
            }
        }

        return $result;
    }

    /**
     * Function to retrieve all courses
     *
     * @param $peoplesoft_id
     *
     * @return array
     */
    public function courses($peoplesoft_id)
    {
        /** @var User $user */
        $user = $this->getUserObject($peoplesoft_id);
        if (!$user){
            return [];
        }

        $result = [];
        /** @var CourseSubscription $CSObject */
        foreach($user->getCourseSubscription() as $CSObject){
            $course = $CSObject->getCourse();
            if (!array_search($course->getId(), array_column($result, 'id'))) {
                array_push($result, [
                    "id"           => $course->getId(),
                    "title"        => $course->getName(),
                    "start_date"   => $course->getStartDate(),
                    "end_date"     => $course->getEndDate(),
                    "programme"    => [
                        "id"    => $course->getProgramme()->getId(),
                        "title" => $course->getProgramme()->getName()
                    ],
                    "url"          => "/app/".$course->getProgramme()->getId()."/home"
                ]);
            }
        }

        return $result;
    }

    /**
     * Function to return User object
     *
     * @param $peoplesoft_id
     * @return object|null
     */
    private function getUserObject($peoplesoft_id){
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peoplesoft_id]);
    }

}
