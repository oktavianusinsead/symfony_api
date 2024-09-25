<?php

namespace esuite\MIMBundle\Controller;

use \DateTime;

use esuite\MIMBundle\Entity\LinkedDocument;
use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use esuite\MIMBundle\Entity\Link;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Exception\InvalidResourceException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\edotExtract;
use esuite\MIMBundle\Service\edotNotify;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Link")]
class LinkController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                private readonly edotExtract $edotExtract,
                                private readonly edotNotify $edotNotify)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
    }

    /**
     * @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "Link";

    #[Put("/links")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Link a new Link. This API endpoint is restricted to coordinators only.")]
    public function createLinkAction(Request $request)
    {
        $this->setLogUuid($request);

        $extract = $this->edotExtract;
        $meta = $extract->url($request->get('url'));

        /** @var Session $session */
        $session = $this->findById('Session', $request->get('session_id'));

        $course = $session->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        //get title for the link
        $title =  $request->get('title');

        //check if url exists
        if ($request->get('url')  ) {

            $link = new Link();
            $link->setSession($session);

            if( $title ) {
                $link->setTitle($title);
            }

            if( $meta->description ) {
                $link->setDescription($meta->description);
            }

            if( $meta->thumbnail_url ) {
                $link->setThumbnail($meta->thumbnail_url);
            }

            if($request->get('due_date')) {
                $link->setDueDate(new \DateTime($request->get('due_date')));
            }

            $link->setDocumentType($request->get('document_type'));
            $link->setUrl( $this->customUrlEncode($request->get('url')) );
            $link->setPosition($request->get('position'));
            if($request->get('publish_at')) {
                $link->setPublishAt(new \DateTime($request->get('publish_at')));
            }

            // Push notifications
            $notify = $this->edotNotify;
            $notify->setLogUuid($request);
            $notify->message($course, self::$ENTITY_NAME);

            $responseObj = $this->create(self::$ENTITY_NAME, $link);
            return $responseObj;

        } else {
            throw new InvalidResourceException(['URL' => ['Please enter valid URL.']]);
        }

    }

    /**
     *  @param $url
     *
     *  @return bool
     *  Validates URL if it exists
     */
    private function checkIfUrlExists($url)
    {
        try {
            stream_context_set_default( [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $headers = get_headers($url);
        } catch( Exception $e ) {
            $this->log( "Error while checking url " . $e->getCode() . ": " . $e->getMessage() );

            return false;
        }

        if ( !isset($headers[0]) ) {
            //empty header
            return false;
        }

        //do not block the process if we have a header
        return true;
    }

    #[Post("/links/{linkId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "linkId", description: "id of the link to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Link. This API endpoint is restricted to coordinators only.")]
    public function updateLinkAction(Request $request, $linkId)
    {
        $this->setLogUuid($request);

        $this->log("UPDATING LINK:" . $linkId);

        $this->validateRelationshipUpdate('session_id', $request);

        // Find the Link
        /** @var LinkedDocument $link */
        $link = $this->findById(self::$ENTITY_NAME, $linkId);

        $course = $link->getSession()->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        // Set new values for Link
        if($request->get('due_date')) {
            $link->setDueDate(new \DateTime($request->get('due_date')));
        }
        if( !is_null($request->get('document_type')) ) {
            $link->setDocumentType($request->get('document_type'));
        }

        if($request->request->has('position')) {
            $link->setPosition( (int)$request->get('position'));
        }
        if($request->get('publish_at')) {
            $link->setPublishAt(new \DateTime($request->get('publish_at')));
        }
        if( !is_null($request->get('title')) ) {
            $link->setTitle($request->get('title'));
        }

        if((new DateTime($request->get('publish_at'))) < new DateTime()) {
            //Push notifications
            $notify = $this->edotNotify;
            $notify->setLogUuid($request);
            $notify->message($course, self::$ENTITY_NAME);
        }

        $responseObj = $this->update(self::$ENTITY_NAME, $link);

        return $responseObj;
    }

    #[Get("/links/{linkId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "linkId", description: "id of the link to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a Link.")]
    public function getLinkAction(Request $request, $linkId)
    {
        $this->setLogUuid($request);

        $responseObj = $this->findById(self::$ENTITY_NAME, $linkId);
        return [strtolower(self::$ENTITY_NAME) => $responseObj];
    }

    #[Get("/links")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Links.")]
    public function getLinksAction(Request $request)
    {
        $this->setLogUuid($request);

        $links = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("LINK: ".$id);
            array_push($links, $this->findById(self::$ENTITY_NAME, $id));
        }
        return ['links' => $links];
    }

    #[Delete("/links/{linkId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "linkId", description: "id of the link to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Link. This API endpoint is restricted to coordinators only.")]
    public function deleteLinkAction(Request $request, $linkId)
    {
        $this->setLogUuid($request);

        $this->log("DELETING LINK: " . $linkId);
        // Find Link that matches id - method with throw 404 if not found
        /** @var Link $link */
        $link = $this->findById(self::$ENTITY_NAME, $linkId);
        $session = $link->getSession();

        $course = $session->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        // Delete Link from database
        $responseObj = $this->deleteById(self::$ENTITY_NAME, $linkId);

        //Push notifications
        $notify = $this->edotNotify;
        $notify->setLogUuid($request);
        $notify->message($course, self::$ENTITY_NAME);

        //update session updated at
        $session->setUpdatedValue();
        $em = $this->doctrine->getManager();
        $em->persist($session);
        $em->flush();

        return $responseObj;
    }

}
