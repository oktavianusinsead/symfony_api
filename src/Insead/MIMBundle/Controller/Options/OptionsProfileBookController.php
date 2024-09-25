<?php

namespace Insead\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;

use Insead\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsProfileBookController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/profile-books/{programmeId}")]
    public function optionsGenerateProfileBookAction($programmeId) {}
    
    #[Options("/profile-books/{peoplesoftId}/avatar")]
    public function optionsGetProfileBookAvatarAction($peoplesoftId) {}
    
    #[Options("/profile-books/{peoplesoftId}/person")]
    public function optionsGetProfileBookInfoAction($peoplesoftId) {}
    
    #[Options("/profile-books/{peoplesoftId}/programmes")]
    public function optionsGetProfileBookProgrammeAction($peoplesoftId) {}
    
    #[Options("/profile-books/{programmeId}/timestamp")]
    public function optionsGetProfileBookTimestampAction($programmeId) {}
    
    #[Options("/outdated-profile-books")]
    public function optionsGetOutdatedProfileBooksAction() {}
}
