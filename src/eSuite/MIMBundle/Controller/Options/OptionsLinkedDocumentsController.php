<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsLinkedDocumentsController extends BaseController
{
    #[Options("/linked-documents/{id}")]
    public function optionsLinkedDocumentAction($id) {}

    #[Options("/linked-documents")]
    public function optionsLinkedDocumentsAction() {}
}
