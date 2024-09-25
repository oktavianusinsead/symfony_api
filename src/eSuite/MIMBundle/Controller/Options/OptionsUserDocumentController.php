<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
/**
 * Class OptionsUserController
 *
 * @package esuite\MIMBundle\Controller
 **/
class OptionsUserDocumentController extends BaseController
{

    #[Options("/profile/read-documents/{id}")]
    public function optionsProfileReadDocumentAction($id)
    {
    }

    #[Options("/profile/read-documents")]
    public function optionsProfileReadDocumentsAction()
    {
    }
}
