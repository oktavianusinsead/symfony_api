<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Insead\MIMBundle\Entity\Course;
use Insead\MIMBundle\Entity\CourseBackup;
use Insead\MIMBundle\Entity\CourseBackupEmail;
use Insead\MIMBundle\Entity\CourseSubscription;
use Insead\MIMBundle\Entity\UserProfileCache;
use Insead\MIMBundle\Entity\UserToken;
use Insead\MIMBundle\Exception\ForbiddenException;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Service\Redis\Base as Redis;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class CourseBackupManager extends Base
{
    protected $redis;

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Course";

    public function loadServiceManager(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Function to retrieve the download link of the backup zip file
     *
     * @param $courseId
     *
     * @return array
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     * @throws ForbiddenException
     */
    public function getCourseBackupLink(Request $request, $courseId) {

        // Find Course
        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if ($course) {
            // Check if User has access to the course
            $subscription = $this->entityManager
                ->getRepository(CourseSubscription::class)
                ->findOneBy(['course' => $course, 'user' => $this->getCurrentUserObj($request)]);

            if ($subscription === null) {
                throw new ForbiddenException('You do not seem to have access to this Course.');
            }
        } else {
            throw new ForbiddenException('You do not seem to have access to this Course.');
        }

        // Check if Backup already exists
        // If it exists, return S3 URL
        // If it does not exists, return a message saying a backup generation command has been initiated and will be available after some time
        $courseBackup = $this->entityManager
            ->getRepository(CourseBackup::class)
            ->findOneBy(['course' => $course]);

        if (!$courseBackup) {
            $this->log("Course Backup record not found");

            $courseBackup = new CourseBackup();
            $courseBackup->setCourse($course);
            $courseBackup->setInProgress(TRUE);

            // Validate Data
            $this->validateObject($courseBackup);

            $em = $this->entityManager;
            $em->persist($courseBackup);
            $em->flush();

            // Insert a message into redis
            $this->redis->set( "course_backup_request", $course->getId() );

            // Return a 404
            throw new ResourceNotFoundException('Course Backup not available for download yet.');

        } else {
            $this->log("Course Backup record found");

            if($courseBackup->getInProgress()) {
                // Return a 404
                throw new ResourceNotFoundException('Course Backup not available for download yet.');
            }

            // Get User's Preferred Email
            //Get Authorization Header
            $authToken = $request->headers->get('Authorization');
            $preferredEmail = $this->getPrefEmail($authToken);

            $downloadURL = $this->backup->generateTempUrl($courseId,$preferredEmail);
            return ['backup' => $courseBackup->setS3Path($downloadURL)];
        }
    }

    /**
     * Function to queue a course for backup
     *
     * @param Request $request Request Object
     * @param String $courseId id of the Course
     *
     * @return Response
     * @throws ForbiddenException
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function notifyCourseBackup(Request $request, $courseId) {

        $this->log("Queuing Course " . $courseId . " for backup.");

        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy(['id' => $courseId]);

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        // Check if User has access to the course
        $subscription = $this->entityManager
            ->getRepository(CourseSubscription::class)
            ->findOneBy(['course' => $course, 'user' => $this->getCurrentUserObj($request)]);

        if ($subscription === null) {
            throw new ForbiddenException('You do not seem to have access to this Course.');
        }

        // Check if an entry for backup for this course from the same user already exists
        $courseBackupEmail = $this->entityManager
            ->getRepository(CourseBackupEmail::class)
            ->findOneBy(['course' => $course, 'user' => $this->getCurrentUserObj($request)]);

        //if the course backup item is existing, process the response
        if($courseBackupEmail) {
            $this->log("Queued Course Backup found in database");

            $response = new Response( json_encode(["queued"=>true, "email" => $courseBackupEmail->getUserEmail()]) );
            $response->setStatusCode(200);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        // Get User's Preferred Email
        //Get Authorization Header
        $authToken = $request->headers->get('Authorization');
        $preferredEmail = $this->getPrefEmail($authToken);
        $this->log( "Preparing Course Backup as requested by " . $preferredEmail );

        $courseBackupEmail = new CourseBackupEmail();
        $courseBackupEmail->setCourse($course);
        $courseBackupEmail->setUser($this->getCurrentUserObj($request));
        $courseBackupEmail->setUserEmail($preferredEmail);

        // Validate Data
        $this->validateObject($courseBackupEmail);

        $em = $this->entityManager;
        $em->persist($courseBackupEmail);
        $em->flush();

        $response = new Response( json_encode(["queued"=>true, "email" => $preferredEmail]) );
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @param $authToken
     * @return string
     * @throws ForbiddenException
     */
    private function getPrefEmail($authToken){
        $authToken = substr( (string) $authToken, 7 );
        /** @var UserToken $loggedInUserToken */
        $loggedInUserToken = $this->entityManager->getRepository(UserToken::class)->findOneBy(['oauth_access_token' => $authToken]);
        if ($loggedInUserToken) {
            if ($loggedInUserToken->getUser()){
                if ($loggedInUserToken->getUser()->getCacheProfile()){
                    /** @var UserProfileCache $cacheProfile */
                    $cacheProfile = $loggedInUserToken->getUser()->getCacheProfile();
                    $prefEmail = $cacheProfile->getPreferredEmail();
                    $preferredEmail = match ($prefEmail) {
                        0 => $cacheProfile->getPersonalEmail(),
                        1 => $cacheProfile->getWorkEmail(),
                        default => $cacheProfile->getUpnEmail(),
                    };

                    if ($preferredEmail === ""){
                        $preferredEmail = $cacheProfile->getUpnEmail();
                    }
                } else {
                    throw new ForbiddenException("1");
                }
            } else {
                throw new ForbiddenException("2");
            }
        } else {
            throw new ForbiddenException("3");
        }

        return $preferredEmail;
    }

}
