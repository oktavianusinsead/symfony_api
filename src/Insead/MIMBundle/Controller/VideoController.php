<?php

namespace Insead\MIMBundle\Controller;

use \DateTime;

use Insead\MIMBundle\Entity\Session;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Insead\MIMBundle\Entity\Video;
use Insead\MIMBundle\Attributes\Allow;
use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Service\StudyNotify;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Documents")]
class VideoController extends BaseController
{
    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Video";

    /**
     * @throws InvalidResourceException
     * @throws \Exception
     */
    #[Put("/videos")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Link a new Video.")]
    public function createVideoAction(Request $request, StudyNotify $studyNotify)
    {
        $this->setLogUuid($request);

        $paramList = "title,description,due_date,document_type,duration,url,position,publish_at";
        $data = $this->loadDataFromRequest( $request, $paramList );

        $this->log("VIDEO TITLE: ".$request->get('title'));
        $this->log("Creating a Video Link: ".$request->get('url')." for session_id: ".$request->get('session_id'));

        /** @var Session $session */
        $session = $this->findById('Session', $request->get('session_id'));

        $course = $session->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        $video = new Video();
        $video->setSession($session);

        $video = $this->processVideo( $video, $data );

        // Push notifications
        $notify = $studyNotify;
        $notify->setLogUuid($request);
        $notify->message($course, self::$ENTITY_NAME);

        return $this->create(self::$ENTITY_NAME, $video);
    }

    /**
     * @throws InvalidResourceException
     * @throws \Exception
     */
    #[Post("/videos/{videoId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Parameter(name: "videoId ", description: "Video Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Video Link.")]
    public function updateVideoAction(Request $request, $videoId, StudyNotify $studyNotify)
    {
        $this->setLogUuid($request);

        $paramList = "title,description,due_date,document_type,duration,url,position,publish_at";
        $data = $this->loadDataFromRequest( $request, $paramList );

        $this->log("UPDATING VIDEO:".$videoId);

        // Find the Video Link
        /** @var Video $video */
        $video = $this->findById(self::$ENTITY_NAME, $videoId);

        $video = $this->processVideo( $video, $data );

        $course = $video->getSession()->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        if((new DateTime($request->get('publish_at'))) < new DateTime()) {
            //Push notifications
            $notify = $studyNotify;
            $notify->setLogUuid($request);
            $notify->message($course, self::$ENTITY_NAME);
        }

        return $this->update(self::$ENTITY_NAME, $video);
    }

    #[Get("/videos/{videoId}")]
    #[Allow(["scope" => "studyadmin,studysuper,mimstudent,studystudent"])]
    #[OA\Parameter(name: "videoId ", description: "Video Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Video Link.")]
    public function getVideoAction(Request $request, $videoId)
    {
        $this->setLogUuid($request);

        $responseObj = $this->findById(self::$ENTITY_NAME, $videoId);
        return [strtolower(self::$ENTITY_NAME) => $responseObj];
    }

    #[Get("/videos")]
    #[Allow(["scope" => "studyadmin,studysuper,mimstudent,studystudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Videos.")]
    public function getVideosAction(Request $request)
    {
        $this->setLogUuid($request);

        $videos = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("VIDEO: ".$id);
            array_push($videos, $this->findById(self::$ENTITY_NAME, $id));
        }
        return ['videos' => $videos];
    }

    /**
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     */
    #[Delete("/videos/{videoId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Video Link.")]
    public function deleteVideoAction(Request $request, $videoId, StudyNotify $studyNotify)
    {
        $this->setLogUuid($request);

        $this->log("DELETING VIDEO: " . $videoId);
        // Find Video that matches id - method with throw 404 if not found
        /** @var Video $video */
        $video = $this->findById(self::$ENTITY_NAME, $videoId);

        $session = $video->getSession();

        $course = $session->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        // Delete Video from database
        $responseObj = $this->deleteById(self::$ENTITY_NAME, $videoId);

        // Push notifications
        $notify = $studyNotify;
        $notify->setLogUuid($request);
        $notify->message($course, self::$ENTITY_NAME);

        //update session updated at
        $session->setUpdatedValue();
        $em = $this->doctrine->getManager();
        $em->persist($session);
        $em->flush();

        return $responseObj;
    }

    /**
     * Process Video field data
     *
     * @param Video $video video object that would be updated
     * @param array $data array containing the data that would be passed to the video object
     *
     * @return Video
     * @throws \Exception
     */
    private function processVideo($video, $data) {
        if($data['title']) {
            $video->setTitle($data['title']);
        }
        if($data['description']) {
            $video->setDescription($data['description']);
        } else {
            $video->setDescription('Embedded video');
        }

        if($data['due_date']) {
            $video->setDueDate(new \DateTime($data['due_date']));
        }

        if( isset($data['document_type']) ) {
            $video->setDocumentType($data['document_type']);
        }

        if( isset($data['duration']) ) {
            $video->setDuration($data['duration']);
        } else {
            $video->setDuration(10);
        }

        if($data['url']) {
            $video->setUrl($data['url']);
        }

        if( (isset($data['position']) && !empty($data['position'])) ) {
            $video->setPosition($data['position']);
        } else {
            $video->setPosition(0);
        }

        if($data['publish_at']) {
            $video->setPublishAt(new \DateTime($data['publish_at']));
        }

        return $video;
    }
}
