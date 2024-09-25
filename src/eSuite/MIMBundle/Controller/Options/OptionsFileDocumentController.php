<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsFileDocumentController extends BaseController
{
    #[Options("/file-documents/{id}")]
    public function optionsFileDocumentAction($id) {}

    #[Options("/file-documents")]
    public function optionsFileDocumentsAction() {}

    #[Options("/file-documents/{id}/url")]
    public function optionsFileDocumentUrlAction($id) {}

    #[Options("/file/upload/{eventid}/user/{userid}")]
    public function optionsUserDocsAction($eventid, $userid) {}

    #[Options("/file/list/{eventid}/user/{userid}")]
    public function optionsListUserDocsAction($eventid, $userid) {}
}
