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
class OptionsUserFavoriteController extends BaseController
{
    #[Options("/profile/favourite-documents/{id}")]
    public function optionsProfileFavouriteDocumentAction($id)
    {
    }

    #[Options("/profile/favourite-documents")]
    public function optionsProfileFavouriteDocumentsAction()
    {
    }

}
