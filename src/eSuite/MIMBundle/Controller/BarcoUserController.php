<?php

namespace esuite\MIMBundle\Controller;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use esuite\MIMBundle\Service\AIPService;
use esuite\MIMBundle\Service\Barco\User;
use esuite\MIMBundle\Service\Barco\UserGroups;
use esuite\MIMBundle\Service\Manager\Base as ManagerBase;
use esuite\MIMBundle\Service\Manager\UserCheckerManager;
use esuite\MIMBundle\Service\Manager\UserProfileManager;
use esuite\MIMBundle\Service\Manager\UtilityManager;
use esuite\MIMBundle\Service\RestHTTPService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;

use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Service\Manager\BarcoManager;
use OpenApi\Attributes as OA;

#[OA\Tag("Barco")]
class BarcoUserController extends BarcoUserBaseController
{

    #[Get("/barco-update-user-peoplesoft_id")]
    #[OA\Response(
        response: 200,
        description: "Handler function to set peoplesoft_id of the barco user which do not have a peoplesoft_id set on their details. User with no peoplesoft_if will occur when user is created manually from Barco")]
    public function getCleanBarcoPeoplesoftIDAction(BarcoManager $barcoManager)
    {
        return $this->barcoManager->cleanBarcoPeoplesoftID();
    }
    
    #[Get("/barco-users")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to get list of users in a group by groupId")]
    public function getBarcoUserGroupUsersAction(Request $request)
    {
        return $this->barcoManager->getUserFromGroup($request);
    }

    #[Post("/barco-users/search")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function search a user")]
    public function getBarcoUserAction(Request $request)
    {
        return $this->barcoManager->getBarcoUser($request);
    }

    #[Post("/barco-users/update")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function update a user and update in barco")]
    public function updateBarcoUserAction(Request $request)
    {
        return $this->barcoManager->updateBarcoUser($request);
    }

    #[Delete("/barco-users/{id}")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to delete a user")]
    public function deleteBarcoUsersAction(Request $request, $id)
    {
        return $this->barcoManager->deleteUser($request, $id);
    }

    #[Get("/barco-users/nonesuite")]
    #[Allow(["scope" => "edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to get all non esuite email account")]
    public function getBarcoNonesuiteUsersAction()
    {
        return $this->barcoManager->getAllNonesuite();
    }
}
