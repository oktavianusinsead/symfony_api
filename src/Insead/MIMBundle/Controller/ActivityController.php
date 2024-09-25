<?php

namespace Insead\MIMBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Insead\MIMBundle\Entity\Activity;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\CalendarManager;
use Insead\MIMBundle\Service\Manager\LoginManager;
use Insead\MIMBundle\Service\Redis\AuthToken;
use Insead\MIMBundle\Service\S3ObjectManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use Insead\MIMBundle\Attributes\Allow;
use Insead\MIMBundle\Service\Manager\ActivityManager;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Activity")]
class ActivityController extends BaseController
{
    public function __construct(LoggerInterface $logger,
                                ManagerRegistry $doctrine,
                                ParameterBagInterface $baseParameterBag,
                                ManagerBase $base,
                                public ActivityManager $activityManager,
                                CalendarManager $calendar,
                                AuthToken $authToken,
                                LoginManager $login)
    {
        parent::__construct($logger, $doctrine, $baseParameterBag, $base);
        $s3 = new S3ObjectManager($baseParameterBag->get('study.s3.config'), $logger);
        $login->loadServiceManager($authToken, $this->baseParameterBag->get('acl.config'));
        $calendar->loadServiceManager($s3, $login);
        $this->activityManager->loadServiceManager($calendar);
    }
    
    #[Put("/activities")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[Security(name:"Bearer")]
    #[OA\Response(
        response: 200,
        description: "Handler function to Create a new Activity. This API endpoint is restricted to coordinators only",
        content: new Model(type: Activity::class))]
    public function createActivityAction(Request $request)
    {
        return $this->activityManager->createActivity($request);
    }
    
    #[Post("/activities/{activityId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[Security(name:"Bearer")]
    #[OA\Response(
        response: 200,
        description: "Handler function to Update an Activity. This API endpoint is restricted to coordinators only",
        content: new Model(type: Activity::class))]
    public function updateActivityAction(Request $request, $activityId)
    {

        return $this->activityManager->updateActivity($request,$activityId);
    }

    #[Get("/activities/{activityId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[Security(name:"Bearer")]
    #[OA\Response(
        response: 200,
        description: "Handler function to get an activity. This API endpoint is restricted to coordinators only",
        content: new Model(type: Activity::class))]
    public function getActivityAction(Request $request, $activityId)
    {
        return $this->activityManager->getActivity($request,$activityId);
    }

    #[Get("/activities")]
    #[Allow(["scope" => "studyadmin,studysuper,mimstudent,studystudent"])]
    #[Security(name:"Bearer")]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple Activity. This API endpoint is restricted to coordinators only")]
    public function getActivitiesAction(Request $request)
    {
        return $this->activityManager->getActivities($request);
    }

     #[Delete("/activities/{activityId}")]
     #[Allow(["scope" => "studyadmin,studysuper"])]
     #[OA\Response(
         response: 200,
         description: "Handler function to Delete an Activity. This API endpoint is restricted to coordinators only")]
    public function deleteActivityAction(Request $request, $activityId)
    {
        return $this->activityManager->deleteActivity($request, $activityId);
    }

}
