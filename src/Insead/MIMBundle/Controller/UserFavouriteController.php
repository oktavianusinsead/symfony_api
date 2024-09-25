<?php

namespace Insead\MIMBundle\Controller;

use Exception;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Exception\InvalidResourceException;
use Insead\MIMBundle\Exception\ConflictFoundException;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Entity\UserFavourite;
use Doctrine\Persistence\ManagerRegistry;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserFavouriteController extends BaseUserController
{
    public static $DOCUMENT_TYPE = ['file_document', 'linked_document', 'video', 'link'];

    #[Post("/profile/favourite-documents/{id}")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "id", description: "Document Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to mark a document as favourite.")]
    public function updateFavouriteDocumentsAction(Request $request, $id )
    {
        $this->setLogUuid($request);

        $UserFavourite = new UserFavourite();

        $UserFavourite = $this->updateUserDocuments( $request, $UserFavourite, $id );

        try {
            $this->create('UserFavourite', $UserFavourite);
        } catch (Exception) {
            throw new ConflictFoundException('favourite document has been updated');
        }

        return $this->getAllUserFavouriteDocs($request);
    }

    #[Delete("/profile/favourite-documents/{id}")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "id", description: "Document Id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to unmark a document as favourite.")]
    public function deleteFavouriteDocumentAction(Request $request, $id)
    {
        $this->setLogUuid($request);

        $criteria = $this->processBeforeDelete( $request, $id, self::$DOCUMENT_TYPE );

        $userFavouriteCollection = $this->findBy('UserFavourite', $criteria);
        /** @var UserFavourite $userFavourite */
        $userFavourite = $userFavouriteCollection[0];
        $userFavouriteId = $userFavourite->getId();
        return $this->deleteById('UserFavourite', $userFavouriteId);
    }

    #[Get("/profile/favourite-documents")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to get user document marked as favourite.")]
    public function getFavouriteDocumentsAction(Request $request)
    {
        $this->setLogUuid($request);

        //  Make sure we have the right parameter
        $courseId = $request->query->get('course');
        if ($courseId) {
            return $this->getAllUserFavouriteDocs($request,$courseId);
        }

        return $this->getAllUserFavouriteDocs($request);
    }

    #[Get("/profile/favourite-documents")]
    #[Allow(["scope" => "mimstudent,studystudent"])]
    #[OA\Parameter(name: "courseId", description: "(optional) Course Id requested", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Get all UserFavouriteDocuments by Course.")]
    private function getAllUserFavouriteDocs(Request $request,$courseId = null)
    {
        $favouriteDocuments = $this->doctrine->getRepository(UserFavourite::class);

        try {
            if (is_null($courseId)) {
                $favouriteDocumentsData = $favouriteDocuments->findBy(['user' => $this->getCurrentUserObj($request)], ['document_type'=>'ASC']);
            } else {
                $favouriteDocumentsData = $favouriteDocuments->findBy(['user' => $this->getCurrentUserObj($request), 'course' => $this->findById('Course', $courseId)], ['document_type'=>'ASC']);
            }
        } catch (Exception) {
            $favouriteDocumentsData = [];
        }

        $result = ['file_documents' => [], 'linked_documents' => [], 'videos' => [], 'links' => []];

        foreach ($favouriteDocumentsData as $document) {
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
