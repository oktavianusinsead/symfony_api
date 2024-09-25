<?php
/**
 * Created by PhpStorm.
 * User: jeffersonmartin
 * Date: 12/11/18
 * Time: 3:52 PM
 */

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsSessionSheetController extends BaseController
{

    #[Options("/session-sheets/{id}")]
    public function optionsGetSessionSheetAction($id){}
    
    #[Options("/session-sheets/{id}/generate")]
    public function optionsGenerateSessionSheetPDFAction($id){}
}
