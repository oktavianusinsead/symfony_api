<?php

namespace Insead\MIMBundle\Controller;

use Insead\MIMBundle\Entity\BaseUser;
use Insead\MIMBundle\Entity\FileDocument;
use Insead\MIMBundle\Entity\Link;
use Insead\MIMBundle\Entity\LinkedDocument;
use Insead\MIMBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;

use Insead\MIMBundle\Exception\InvalidResourceException;

/**
 * Class UserBaseController
 *
 * @package Insead\MIMBundle\Controller
 **/
class BaseUserController extends BaseController
{
    public static $DOCUMENT_TYPE = ['file_document', 'linked_document', 'video', 'link'];

    protected function updateUserDocuments(Request $request, BaseUser $baseUser, $id)
    {
        //  Make sure we have the right parameter
        $data = $request->request->all();

        if (!array_key_exists('type', $data)) {
            throw new InvalidResourceException('Missing parameter type');
        }

        // Make sure to accept right Object type
        if (!in_array($data['type'], self::$DOCUMENT_TYPE)) {
            throw new InvalidResourceException('Document type not supported');
        }

        $documentType = $data['type'];
        $documentEntity = '';
        switch ($documentType) {
            case self::$DOCUMENT_TYPE[0]:
                $documentEntity = 'FileDocument';
                break;
            case self::$DOCUMENT_TYPE[1]:
                $documentEntity = 'LinkedDocument';
                break;
            case self::$DOCUMENT_TYPE[2]:
                $documentEntity = 'Video';
                break;
            case self::$DOCUMENT_TYPE[3]:
                $documentEntity = 'Link';
                break;
        }

        /** @var FileDocument|LinkedDocument|Video|Link $attachment */
        $attachment = $this->findById($documentEntity, $id);

        // Get Course
        $course = $attachment->getSession()->getCourse();

        $documentInfo = [
            'document_id' => $id,
            'document_type' => $documentType,
            'course_id' => $course->getId()
        ];

        // Save id to UserFavourite
        $baseUser->setUser($this->getCurrentUserObj($request));
        switch ($documentType) {
            case self::$DOCUMENT_TYPE[0]:
                $baseUser->setFileDocument($attachment);
                break;
            case self::$DOCUMENT_TYPE[1]:
                $baseUser->setLinkDocument($attachment);
                break;
            case self::$DOCUMENT_TYPE[2]:
                $baseUser->setVideo($attachment);
                break;
            case self::$DOCUMENT_TYPE[3]:
                $baseUser->setLink($attachment);
                break;
        }
        if( isset($documentInfo['document_type']) ) {
            $baseUser->setDocumentType($documentInfo['document_type']);
        }
        $baseUser->setCourse($course);

        return $baseUser;
    }

    protected function processBeforeDelete( $request, $id, $documentTypeCollection )
    {
        //  Make sure we have the right parameter
        $data = $request->request->all();
        if (!array_key_exists('type', $data)) {
            throw new InvalidResourceException('Missing parameter type not found');
        }

        // Make sure to accept right document type
        if (!in_array($data['type'], $documentTypeCollection)) {
            throw new InvalidResourceException('Document type not supported');
        }

        //Find object details
        $documentType = $data['type'];
        $criteria = array_filter([]);

        switch ($documentType) {
            case $documentTypeCollection[0]:
                $fileDocument = $this->findById('FileDocument', $id);
                $criteria = array_filter(['user' => $this->getCurrentUserObj($request), 'filedocument' => $fileDocument, 'document_type' => $documentType]);
                break;
            case $documentTypeCollection[1]:
                $linkedDocument = $this->findById('LinkedDocument', $id);
                $criteria = array_filter(['user' => $this->getCurrentUserObj($request), 'linkeddocument' => $linkedDocument, 'document_type' => $documentType]);
                break;
            case $documentTypeCollection[2]:
                $video = $this->findById('Video', $id);
                $criteria = array_filter(['user' => $this->getCurrentUserObj($request), 'video' => $video, 'document_type' => $documentType]);
                break;
            case $documentTypeCollection[3]:
                $link = $this->findById('Link', $id);
                $criteria = array_filter(['user' => $this->getCurrentUserObj($request), 'link' => $link, 'document_type' => $documentType]);
                break;
        }

        return $criteria;
    }

}
