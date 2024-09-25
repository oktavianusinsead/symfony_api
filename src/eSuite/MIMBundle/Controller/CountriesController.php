<?php

namespace esuite\MIMBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use esuite\MIMBundle\Attributes\Allow;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Service\Manager\CountryManager;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Country")]
class CountriesController extends BaseController
{
    #[Get("/countries")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve Countries")]
    public function getCountriesAction(Request $request, CountryManager $countryManager)
    {
        $this->setLogUuid($request);

        return $countryManager->getCountries();
    }

    #[Get("/states")]
    #[Allow(["scope" => "mimstudent,edotstudent,edotadmin,edotsuper"])]
    #[OA\Parameter(name: "request", description: "request object sent to the endpoint", in: "query", schema: new OA\Schema(type: "Request"))]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve States")]
    public function getStatesAction(Request $request, CountryManager $countryManager)
    {
        $this->setLogUuid($request);

        return $countryManager->getStatesList();
    }
}
