<?php

namespace esuite\MIMBundle\Controller\Options;

use esuite\MIMBundle\Controller\BaseController;
use esuite\MIMBundle\Entity\UserToken;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Options")]
class OptionsUserTokenController extends BaseController
{
    #[Options("/user-tokens")]
    public function optionsUserTokenAction() {}

    #[Options("/user-tokens/search")]
    public function optionsUserTokenSearchAction($tokenSearchCriterion) {}

    #[Options("/archive/user-tokens")]
    public function optionsArchiveUserTokensAction(){}
}
