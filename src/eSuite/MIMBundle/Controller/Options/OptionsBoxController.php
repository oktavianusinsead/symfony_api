<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsBoxController extends BaseController
{
    #[Options("/box/oauth")]
    public function optionsBoxAuthCodeAction() {}

    #[Options("/box/folders")]
    public function optionsFoldersAction() {}

    #[Options("/box/folders/{id}/items")]
    public function optionsFolderAction($id) {}

    #[Options("/box/files/{id}/content")]
    public function optionsFileAction($id) {}

    #[Options("/box/files")]
    public function optionsFilesAction() {}

    #[Options("/migrate-programme-files/{programmeId}")]
    public function optionsProgrammesFilesAction() {}

}
