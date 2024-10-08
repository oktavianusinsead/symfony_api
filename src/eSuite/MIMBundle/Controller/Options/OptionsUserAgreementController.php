<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use FOS\RestBundle\Controller\Annotations\Options;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsUserAgreementController extends BaseController
{

    #[Options("/user-agreements")]
    public function optionsUserAgreementsAction() {}

    #[Options("/user-agreement/{peoplesoftId}")]
    public function optionsUserAgreementAction($peoplesoftId) {}
}
