<?php

namespace esuite\MIMBundle\Controller;

use Exception;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ConflictFoundException;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Entity\UserDocument;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserReadDocumentController extends BaseUserController
{

    public static $DOCUMENT_TYPE = ['file_document', 'linked_document', 'video', 'link'];

    #[Post("/profile/read-documents/{id}")]
    #[Allow(["scope" => "mimstudent,edotstudent"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "id", description: "Document Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to mark a document as read.")]
    public function updateReadDocumentsAction(Request $request, $id)
    {
        $this->setLogUuid($request);

        $UserDocument = new UserDocument();

        $UserDocument = $this->updateUserDocuments( $request, $UserDocument, $id );

        try {
            $this->create('UserDocument', $UserDocument);
        } catch (Exception) {
            throw new ConflictFoundException('Read document has been updated.');
        }

        return $this->getAllUserReadDocs($request);
    }

    #[Delete("/profile/read-documents/{id}")]
    #[Allow(["scope" => "mimstudent,edotstudent"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "id", description: "Document Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to unmark a document as Read.")]
    public function deleteReadDocumentAction(Request $request, $id)
    {
        $this->setLogUuid($request);

        $criteria = $this->processBeforeDelete( $request, $id, self::$DOCUMENT_TYPE );

        try {
            $userDocCollection   = $this->findBy('UserDocument', $criteria);
            /** @var UserDocument $userDoc */
            $userDoc = $userDocCollection[0];
            $userDocId = $userDoc->getId();
        } catch (ResourceNotFoundException) {
            throw new ConflictFoundException('Document already unread.');
        }

        return $this->deleteById('UserDocument', $userDocId);
    }

    #[Get("/profile/read-documents")]
    #[Allow(["scope" => "mimstudent,edotstudent"])]
    #[OA\Parameter(name: "request", description: "Expects GET parameter 'course' for course Id", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to get all user document marked as read.")]
    public function getReadDocumentsAction(Request $request)
    {
        $this->setLogUuid($request);

        //  Make sure we have the right parameter
        $courseId = $request->query->get('course');
        if ($courseId) {
            return $this->getAllUserReadDocs($request,$courseId);
        }

        return $this->getAllUserReadDocs($request);
    }


    /**
     * Get all UserReadDocuments by Course
     *
     * @param int $courseId (optional) Course Id requested
     *
     * @return array
     */
    private function getAllUserReadDocs(Request $request,$courseId = null)
    {
        try {
            if (is_null($courseId)) {
                $readDocumentsData = $this->doctrine
                    ->getRepository(UserDocument::class)
                    ->findBy(
                        ['user' => $this->getCurrentUserObj($request)],
                        ['document_type'=>'ASC']
                    );

            } else {
                $readDocumentsData = $this->doctrine
                    ->getRepository(UserDocument::class)
                    ->findBy(
                        ['user' => $this->getCurrentUserObj($request), 'course' => $this->findById('Course', $courseId)],
                        ['document_type'=>'ASC']
                    );
            }
        } catch (Exception) {
            $readDocumentsData = [];
        }

        $result = ['file_documents' => [], 'linked_documents' => [], 'videos' => [], 'links' => []];

        foreach ($readDocumentsData as $document) {
            $documentType = $document->getDocumentType();

            switch ($documentType) {
                case self::$DOCUMENT_TYPE[0]:
                    $result['file_documents'][] = (int)$document->getFileDocument()->getId();
                    break;
                case self::$DOCUMENT_TYPE[1]:
                    $result['linked_documents'][] = (int)$document->getLinkDocument()->getId();
                    break;
                case self::$DOCUMENT_TYPE[2]:
                    $result['videos'][] = (int)$document->getVideo()->getId();
                    break;
                case self::$DOCUMENT_TYPE[3]:
                    $result['links'][] = (int)$document->getLink()->getId();
                    break;
            }
        }

        return $result;
    }

}
