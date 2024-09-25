<?php

namespace Insead\MIMBundle\Service\Barco;

use Insead\MIMBundle\Exception\InvalidResourceException;
use Exception;

class User extends Base
{
    /**
     * Function to add new user  using the Barco API
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @return array()
     * @throws InvalidResourceException
     */
    public function createNewUser($email, $firstName, $lastName) {
        $this->log('Creating new User Name: ' . $firstName . ' ' . $lastName);

        try {
            $parameters = [
                'email' => $email,
                'identifiers' => [
                    'saml' => $email
                ],
                'managesInstitute' => false,
                'name' => [
                    'displayName' => $firstName." ".$lastName,
                    'first'       => $firstName,
                    'last'        => $lastName

                ]
            ];

            $this->headerOptions['json'] = $parameters;
            $response = $this->http->post($this->barcoAPIURL . $this->barcoUser, $this->headerOptions);
            $response = $response->getBody()->getContents();
            return json_decode((string) $response, true);
        } catch (Exception $e){
            $this->log("Error creating user to barco: ".print_r($e->getMessage(), true));
            throw new InvalidResourceException("Unable to create new user: $email");
        }
    }

    /**
     * Function to get user details by ID using the Barco API
     * @param String $userId id of the user in Barco Admin
     *
     * @return array()
     * @throws InvalidResourceException
     */
    public function getUserById($userId) {
        $this->log('Finding Barco User USER ID: ' . $userId);

        try {
            $response = $this->http->get($this->barcoAPIURL . $this->barcoUser . "/" . $userId, $this->headerOptions);
            $response = $response->getBody()->getContents();
            return json_decode((string) $response, true);
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException("Unable to fetch barco user (".$userId.")details");
        }
    }

    /**
     * Function to get user details by ID using the Barco API
     * @param String $userId id of the user in Barco Admin
     *
     * @return mixed
     * @throws InvalidResourceException
     */
    public function deleteUserById($userId) {
        $this->log('Deleting Barco User USER ID: ' . $userId);

        try {
            $response = $this->http->delete($this->barcoAPIURL . $this->barcoUser . "/" . $userId, $this->headerOptions);
            $this->log('Barco User USER ID: ' . $userId . ' has been deleted');
            return $response;
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException("Unable to delete  barco user: ".$userId);
        }
    }

    /**
     * Function to update user details by ID using the Barco API
     * @param $userId
     * @param $parameters
     * @return mixed
     * @throws InvalidResourceException
     */
    public function updateUserById($userId, $parameters) {
        $this->log('Updating Barco User USER ID: ' . $userId);
        $this->headerOptions['json'] = $parameters;

        try {
            $response = $this->http->put($this->barcoAPIURL . $this->barcoUser . "/" . $userId, $this->headerOptions);
            $this->log('Barco User USER ID: ' . $userId . ' has been update');
            return $response;
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException("Unable to update basrco user (".$userId.") details");
        }
    }

    /**
     * Get Barco user by API Key - Value
     * @param $criteria
     * @param $value
     * @return mixed
     * @throws InvalidResourceException
     */
    public function findUserByAPIKey($criteria, $value){
        $this->log('Getting Barco User by API key: ' . $criteria . ' value: ' . $value);
        try {
            $response = $this->http->get($this->barcoAPIURL . $this->barcoUser . "/" . $criteria . ':' . $value, $this->headerOptions);
            $response = $response->getBody()->getContents();
            return json_decode((string) $response, true);
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException("Unable to get Barco User by API key: $criteria value: $value");
        }
    }

    /**
     * Function to Get usergroups to which a user belongs
     * @param String $userId id of the user in Barco Admin
     *
     * @return array()
     * @throws InvalidResourceException
     */
    public function getUsergroupsById($userId) {
        $this->log('Finding Usergroups of USER ID: ' . $userId);

        try {
            $response = $this->http->get($this->barcoAPIURL . $this->barcoUser . "/" . $userId . "/usergroups", $this->headerOptions);
            $response = $response->getBody()->getContents();
            return json_decode((string) $response, true);
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException("Unable to fetch barco user (".$userId.")details");
        }
    }
}
