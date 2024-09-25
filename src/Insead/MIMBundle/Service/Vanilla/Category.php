<?php

namespace Insead\MIMBundle\Service\Vanilla;

use Psr\Log\LoggerInterface;
use Exception;
use Insead\MIMBundle\Exception\VanillaGenericException;

class Category extends Base
{
    protected $url;

    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config,$logger);

        $this->url            = $this->apiUrl . '/categories';
    }

    /**
     * Function to list all categories
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return array()
     */
    public function list() {
        $this->log('Getting all categories');

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {
            $response = $this->http->get($this->url,$options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $this->log("Error occurred while getting list of groups");
            throw new VanillaGenericException($e->getCode(), 'Could not retrieve list of groups');
        }

        return $response;
    }

    /**
     * Function to search categories base on queryString
     * @param String $searchCategoryName
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return array()
     */
    public function search($searchCategoryName){
        $this->log('Search category name: ' . json_encode($searchCategoryName));

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {
            $response = $this->http->get($this->url."/search?query=".urlencode($searchCategoryName),$options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->log("Error occurred while searching categories: ".$message);
            throw new VanillaGenericException($e->getCode(), 'Could not search category');
        }

        return $response;
    }
}
