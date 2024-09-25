<?php

namespace esuite\MIMBundle\Service\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Service\BoxManager\BoxManager;
use esuite\MIMBundle\Service\edotCourseBackup as Backup;
use esuite\MIMBundle\Service\edotNotify;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use esuite\MIMBundle\Entity\Announcement;

use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;

use esuite\MIMBundle\Service\Redis\AdminAnnouncement as Redis;

use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AnnouncementManager extends Base
{

    protected $redis;
    protected $env;
    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Announcement";

    /**
     * AnnouncementManager constructor.
     *
     * @param $config
     */
    public function loadServiceManager(Redis $redis, $config )
    {
        $this->env = $config["symfony_environment"];
        $this->redis = $redis;
    }

    /**
     * Function to create an Announcement
     *
     * @param Request $request Request Object
     *
     * @return Response
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createAnnouncement(Request $request)
    {

        $this->log("Announcements:".$request->get('title'));
        $this->log("Creating Announcements for course_id: ".$request->get('course_id'));

        //Find course
        /** @var Course $course */
        $course = $this->entityManager
            ->getRepository(Course::class)
            ->findOneBy( ['id' => $request->get('course_id')] );

        if(!$course) {
            $this->log('Course not found');
            throw new ResourceNotFoundException('Course not found');
        }

        $this->checkReadWriteAccess($request,$course->getId());

        $userId = $this->getCurrentUserId($request);

        $subscription = $this->entityManager
            ->getRepository(CourseSubscription::class)
            ->findBy( ['user' => $userId, 'course' => $course->getId()] );

        if (sizeof($subscription) == 0) {
            throw new InvalidResourceException(
                ['announcement' => ['You are not authorised to create Announcement to this Course as you are not assigned to it. Please add yourself under "People" section and re-try.']]
            );
        }

        $announcement = new Announcement();
        $announcement->setCourse($course);
        $announcement->setTitle($request->get('title'));
        $announcement->setDescription($request->get('description'));
        if($request->get('peoplesoft_id')) {
            $announcement->setPeoplesoftId($request->get('peoplesoft_id'));
        } else {
            $announcement->setPeoplesoftId($this->getCurrentUserPsoftId($request));
        }

        if($request->get('published') === true) {
            $announcement->setPublishedAt(new \DateTime());
        }
        $announcement->setPublished($request->get('published'));

        $responseObj = $this->createRecord(self::$ENTITY_NAME, $announcement);

        // push notifications if published
        if ($request->get('published')) {
            $this->notify->setLogUuid($request);
            $this->notify->message($course, self::$ENTITY_NAME, $announcement->getTitle());
        }

        return $responseObj;
    }

    /**
     * Function to retrieve an existing Announcement
     *
     * @param Request       $request            Request Object
     * @param String        $announcementId     id of the Announcement
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getAnnouncement(Request $request, $announcementId)
    {

        $this->log("ANNOUNCEMENT: ".$announcementId);

        $announcement = $this->entityManager
            ->getRepository(Announcement::class)
            ->findOneBy( ['id' => $announcementId] );

        if(!$announcement) {
            $this->log('Announcement not found');
            throw new ResourceNotFoundException('Announcement not found');
        }

        return [strtolower(self::$ENTITY_NAME) => $announcement];
    }

    /**
     * Function to retrieve existing Announcements
     *
     * @param Request       $request            Request Object
     *
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getAnnouncements(Request $request)
    {

        $announcements = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("ANNOUNCEMENT: ".$id);

            $announcement = $this->entityManager
                ->getRepository(Announcement::class)
                ->findOneBy( ['id' => $id] );

            if(!$announcement) {
                $this->log('Announcement not found');
                throw new ResourceNotFoundException('Announcement not found');
            }

            array_push($announcements, $announcement);
        }

        return ['announcements' => $announcements];
    }

    /**
     * Function to retrieve existing Announcements
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function getAdminAnnouncement(Request $request)
    {

        $announcements = [];

        $message = $this->redis->getAdminAnnouncementMessage();
        $type = $this->redis->getAdminAnnouncementType();
        $title = $this->redis->getAdminAnnouncementTitle();

        $announcements['message']= ( $message ?? "" );
        $announcements['title']= ( $title ?? "" );
        $announcements['type'] = ( $type ?? "" );
        return ['announcement' => $announcements];
    }


    /**
     * Function to retrieve existing Announcements
     *
     * @param Request       $request            Request Object
     *
     * @return array
     */
    public function updateAdminAnnouncement(Request $request)
    {

        $this->logger->info("Admin ANNOUNCEMENT changing ".$request);

        if($request->get('message')!=""){
            $this->redis->updateAdminAnnouncementMessage($request->get('message'));
        }
        if($request->get('title')!=""){
            $this->redis->updateAdminAnnouncementTitle($request->get('title'));
        }
        if($request->get('type')!=""){
            $this->redis->updateAdminAnnouncementType($request->get('type'));
        }

        return $this->getAdminAnnouncement($request);
    }

    /**
     * Function to update an existing Announcement
     *
     * @param Request $request Request Object
     * @param String $announcementId id of the Announcement
     *
     * @return Response
     *
     * @throws InvalidResourceException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ResourceNotFoundException
     */
    public function updateAnnouncement(Request $request, $announcementId)
    {

        $this->log("ANNOUNCEMENT:".$request->get('title'));

        $this->validateRelationshipUpdate('course_id', $request);

        /** @var $announcement Announcement */
        $announcement = $this->entityManager
            ->getRepository(Announcement::class)
            ->findOneBy( ['id' => $announcementId] );

        if(!$announcement) {
            $this->log('Announcement not found');
            throw new ResourceNotFoundException('Announcement not found');
        }

        $this->checkReadWriteAccess($request,$announcement->getCourseId());

        $wasPublished = $announcement->getPublished();

        // Set new values for Announcement
        if($request->get('title')) {
            $announcement->setTitle($request->get('title'));
        }
        if($request->get('description')) {
            $announcement->setDescription($request->get('description'));
        }
        if($request->get('published')) {
            $announcement->setPublished($request->get('published'));
            $announcement->setPublishedAt(new \DateTime());
        } else {
            if( $request->get('published') === false) {
                $announcement->setPublished(false);
                $announcement->setPublishedAt(null);
            }
        }

        $responseObj = $this->updateRecord(self::$ENTITY_NAME, $announcement);

        // push notifications if published
        if ($request->get('published') || $wasPublished) {
            $this->notify->setLogUuid($request);
            $this->notify->message($announcement->getCourse(), self::$ENTITY_NAME, $announcement->getTitle());
        }

        return $responseObj;
    }

    /**
     * Function to delete an existing Announcement
     *
     * @param Request $request Request Object
     * @param String $announcementId id of the Announcement
     *
     * @return Response
     * @throws ResourceNotFoundException
     */
    public function deleteAnnouncement(Request $request, $announcementId)
    {

        // Get Announcement
        /** @var Announcement $announcement */
        $announcement = $this->entityManager
            ->getRepository(Announcement::class)
            ->findOneBy( ['id' => $announcementId] );

        if(!$announcement) {
            $this->log('Announcement not found');
            throw new ResourceNotFoundException('Announcement not found');
        }

        $this->checkReadWriteAccess($request,$announcement->getCourseId());

        $isAnnouncementPublished = $announcement->getPublished();
        $course = $announcement->getCourse();

        $em = $this->entityManager;
        $em->remove($announcement);
        $em->flush();

        $responseObj = new Response();
        $responseObj->setStatusCode(204);

        // push notifications if course & announcement are published
        if ($course->getPublished() && $isAnnouncementPublished) {
            $this->notify->setLogUuid($request);
            $this->notify->message($course, self::$ENTITY_NAME);
        }

        return $responseObj;
    }
}
