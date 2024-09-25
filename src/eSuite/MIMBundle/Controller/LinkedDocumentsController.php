<?php

namespace esuite\MIMBundle\Controller;

use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use esuite\MIMBundle\Entity\LinkedDocument;
use esuite\MIMBundle\Attributes\Allow;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\edotNotify;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Documents")]
class LinkedDocumentsController extends BaseController
{

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "LinkedDocument";

    /**
     *  @var string
     *  Name of the Entity
     */
    public static $EMBER_NAME = "linked_document";

    #[Put("/linked-documents")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a new Linked Document. This API endpoint is restricted to coordinators only.")]
    public function createLinkedDocAction(Request $request, edotNotify $edotNotify)
    {
        $this->setLogUuid($request);

        $paramList = "title,session_id,description,due_date,document_type,url,position,expiry,publish_at";
        $data = $this->loadDataFromRequest( $request, $paramList );

        $this->log("LINKED DOCUMENT:".$data['title']);

        /** @var Session $session */
        $session = $this->findById('Session', $data['session_id']);

        $this->checkReadWriteAccess($request,$session->getCourseId());

        $linkedDoc = new LinkedDocument();
        $linkedDoc->setSession($session);

        $data['url'] = $this->customUrlEncode( $data['url'] );

        $linkedDoc = $this->processLinkedDoc( $linkedDoc, $data );

        // Push notifications
        $notify = $edotNotify;
        $notify->setLogUuid($request);
        $notify->message($session->getCourse(), self::$ENTITY_NAME);

        $responseObj = $this->create(self::$EMBER_NAME, $linkedDoc);
        return $responseObj;
    }

    #[Post("/linked-documents/{linkedDocId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "linkedDocId", description: "id of linked document to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Linked Document. This API endpoint is restricted to coordinators only.")]
    public function updateLinkedDocAction(Request $request, $linkedDocId, edotNotify $edotNotify)
    {
        $this->setLogUuid($request);

        $paramList = "title,description,due_date,document_type,url,position,expiry,publish_at";
        $data = $this->loadDataFromRequest( $request, $paramList );

        $this->log("UPDATING LINKED DOCUMENT:".$linkedDocId);

        $this->validateRelationshipUpdate('session_id', $request);

        // Find the Linked Doc
        /** @var LinkedDocument $linkedDoc */
        $linkedDoc = $this->findById(self::$ENTITY_NAME, $linkedDocId);

        $course = $linkedDoc->getSession()->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        $linkedDoc = $this->processLinkedDoc( $linkedDoc, $data );

        $responseObj = $this->update(self::$EMBER_NAME, $linkedDoc);

        if((new \DateTime($request->get('publish_at'))) < new \DateTime()) {
            //Push notifications
            $notify = $edotNotify;
            $notify->setLogUuid($request);
            $notify->message($course, self::$ENTITY_NAME);
        }
        return $responseObj;
    }

    #[Get("/linked-documents/{linkedDocId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "linkedDocId", description: "id of retrieve document to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update a Linked Document. This API endpoint is restricted to coordinators only.")]
    public function getLinkedDocAction(Request $request, $linkedDocId)
    {
        $this->setLogUuid($request);

        $linkedDoc = $this->findById(self::$ENTITY_NAME, $linkedDocId);
        $responseObj = $linkedDoc;
        return [strtolower(self::$EMBER_NAME) => $responseObj];
    }

    #[Get("/linked-documents")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Linked Documents. This API endpoint is restricted to coordinators only.")]
    public function getLinkedDocumentsAction(Request $request)
    {
        $this->setLogUuid($request);

        $linkedDocs = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("LINKED DOCUMENT: ".$id);
            array_push($linkedDocs, $this->findById(self::$ENTITY_NAME, $id));
        }
        return ['linked_documents' => $linkedDocs];
    }

    #[Delete("/linked-documents/{linkedDocId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "linkedDocId", description: "id of retrieve document to be updated", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to Delete a Linked Documents. This API endpoint is restricted to coordinators only.")]
    public function deleteLinkedDocAction(Request $request, $linkedDocId, edotNotify $edotNotify)
    {
        $this->setLogUuid($request);

        $this->log("DELETING LINKED DOCUMENT: " . $linkedDocId);

        // Find Linked Document that matches id - method with throw 404 if not found
        /** @var LinkedDocument $linkedDoc */
        $linkedDoc = $this->findById(self::$ENTITY_NAME, $linkedDocId);

        $session = $linkedDoc->getSession();

        $course = $session->getCourse();
        $this->checkReadWriteAccess($request,$course->getId());

        // Delete Linked Document from database
        $responseObj = $this->deleteById(self::$ENTITY_NAME, $linkedDocId);

        //Push notifications
        $notify = $edotNotify;
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
     * Process Linked Document field data
     *
     * @param LinkedDocument $linkedDoc linked document object that would be updated
     * @param array $data array containing the data that would be passed to the linkedDocument object
     *
     * @return LinkedDocument
     * @throws \Exception
     */
    private function processLinkedDoc($linkedDoc, $data) {
        if($data['title']) {
            $linkedDoc->setTitle($data['title']);
        }
        if($data['description']) {
            $linkedDoc->setDescription($data['description']);
        }
        if($data['due_date']) {
            $linkedDoc->setDueDate(new \DateTime($data['due_date']));
        }

        if( isset($data['document_type']) ) {
            $linkedDoc->setDocumentType($data['document_type']);
        }
        $linkedDoc->setMimeType(''); // not currently passed from FE
        if($data['url']) {
            $linkedDoc->setUrl($data['url']);
        }

        if( isset($data['position'])  ) {
            $linkedDoc->setPosition($data['position']);
        }
        if($data['expiry']) {
            $linkedDoc->setExpiry(new \DateTime($data['expiry']));
        }

        if($data['publish_at']) {
            $linkedDoc->setPublishAt(new \DateTime($data['publish_at']));
        }

        return $linkedDoc;
    }

}
