<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\PermissionDeniedException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\LoginManager;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\S3ObjectManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Delete ;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\ProfileBookManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Profile")]
class ProfileBookController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public ProfileBookManager $profileBookManager,
                                AuthToken $authToken,
                                LoginManager $login)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $this->doctrine = $doctrine;
        $this->baseParameterBag = $baseParameterBag;
        $s3 = new S3ObjectManager($baseParameterBag->get('edot.s3.config'), $logger);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $this->profileBookManager->loadServiceManager($s3, $login, $baseParameterBag->get('profilebook.config'));
    }

    #[Post("/profile-books/{programmeId}")]
    #[Allow(["scope" => "edotssvc,edotsvc,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId", description: "programme id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to generate the profile book")]
    public function generateProfileBookAction(Request $request, $programmeId)
    {
        return $this->profileBookManager->generateProfileBook( $request, $programmeId );
    }

    #[Get("/profile-books/{programmeId}")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId", description: "programme id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to extract all Programmes")]
    public function getProfileBookAction(Request $request, $programmeId)
    {
        return $this->profileBookManager->getProfileBook( $request, $programmeId );
    }

    #[Delete("/profile-books/{programmeId}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId", description: "programme id", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to delete all  profile books")]
    public function deleteProfileBooksAction(Request $request, $programmeId)
    {
        return $this->profileBookManager->deleteProfileBooks( $request, $programmeId );
    }

    #[Get("/profile-books/{peoplesoftId}/person")]
    #[Allow(["scope" => "edotssvc,edotsvc,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Get the Encrypted Profile information of a given user")]
    public function getProfileBookPersonAction(Request $request, $peoplesoftId)
    {
        return $this->profileBookManager->getPersonInfo( $request, $peoplesoftId );
    }

    #[Get("/profile-books/{peoplesoftId}/programmes")]
    #[Allow(["scope" => "edotssvc,edotsvc,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Update the Programme Profile Books of the user")]
    public function getProfileBookPersonProgrammesAction(Request $request, $peoplesoftId)
    {
        return $this->profileBookManager->getPersonProgrammes( $request, $peoplesoftId );
    }

    #[Get("/profile-books/{peoplesoftId}/avatar")]
    #[Allow(["scope" => "edotssvc,edotsvc,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "peoplesoftId", description: "PeopleSoftId", in: "query", schema: new OA\Schema(type: "string"))]
    #[OA\Response(
        response: 200,
        description: "Get the Encrypted Profile Avatar of a given user")]
    public function getProfileBookAvatarAction(Request $request, $peoplesoftId)
    {
        return $this->profileBookManager->getAvatarInfo( $request, $peoplesoftId );
    }

    #[Post("/profile-books/{programmeId}/timestamp")]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Parameter(name: "programmeId", description: "Programme ID", in: "query", schema: new OA\Schema(type: "int"))]
    #[OA\Response(
        response: 200,
        description: "Handler function that set the last timestamps of the ProfileBooks")]
    public function setProfileBookTimestampAction(Request $request, $programmeId)
    {
        return $this->profileBookManager->setProfileBookTimestamp( $request, $programmeId );
    }

    #[Get("/outdated-profile-books")]
    #[Allow(["scope" => "edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object passed", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Get list of outdated programmes, programmes that does not have updated profilebooks")]
    public function getOutdatedProfileBooksAction(Request $request)
    {
        return $this->profileBookManager->getOutdatedProfileBooks( $request );
    }

}
