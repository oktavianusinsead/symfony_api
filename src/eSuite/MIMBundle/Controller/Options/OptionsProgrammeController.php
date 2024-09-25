<?php

namespace esuite\MIMBundle\Controller\Options;

use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Controller\BaseController;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsProgrammeController extends BaseController
{
    /**
     *  CORS settings
     */
    #[Options("/programmes")]
    public function optionsProgrammesAction() {}

    #[Options("/programmes/{programmeId}")]
    public function optionsProgrammeAction($id) {}

    #[Options("/programmes/{programmeId}/courses")]
    public function optionsListBelongingCoursesAction($programmeId) {}
    
    #[Options("/programmes/{programmeId}/people")]
    public function optionsListAssignedPeopleAction($programmeId) {}
    
    #[Options("/programmes/{programmeId}/coordinators")]
    public function optionsListCoordinatorsAction($programmeId) {}
    
    #[Options("/programmes/archive/{programmeId}")]
    public function optionsArchiveProgrammeAction($programmeId) {}
    
    #[Options("/programmes/archives")]
    public function optionsArchiveProgrammeListAction($programmeId) {}
    
    #[Options("/programmes/{programmeId}/copy")]
    public function optionsCopyProgrammeAction($programmeId) {}
}
