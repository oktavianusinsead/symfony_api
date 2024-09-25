<?php

namespace esuite\MIMBundle\Controller\Options;


use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsGroupSessionAttachmentController extends BaseController
{
    #[Options("/group-session-attachments")]
    public function optionsGroupSessionsAttachmentAction() {}

    #[Options("/group-session-attachments/{id}")]
    public function optionsGroupSessionAttachmentAction($id) {}
}
