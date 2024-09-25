<?php

namespace esuite\MIMBundle\Service\Barco;

use esuite\MIMBundle\Exception\InvalidResourceException;
use Exception;

class UserGroups extends Base
{
    /**
     * Function to get group details by ID using the Barco API
     * @param $groupId
     *
     * @return array()
     * @throws InvalidResourceException
     */
    public function getUserGroupById($groupId) {
        $this->log('Finding Barco Group ' . $this->env . ' Group ID: ' . $groupId);

        try {
            $response = $this->http->get($this->barcoAPIURL . $this->barcoGroup . "/" . $groupId, $this->headerOptions);
            $response = $response->getBody()->getContents();
            $response = json_decode((string) $response, true);

            return $response;
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException("Unable to fetch user groups details");
        }
    }

    /**
     * Function to get users from group by ID using the Barco API
     * @param $groupId
     *
     * @return array()
     * @throws InvalidResourceException
     */
    public function getUsersFromGroupById($groupId) {
        $this->log('Getting Barco Users for Group ID: ' . $groupId);

        try {
            $response = $this->http->get($this->barcoAPIURL . $this->barcoGroup . "/" . $groupId. "/users?limit=100", $this->headerOptions);
            $response = $response->getBody()->getContents();
            $response = json_decode((string) $response, true);

            return $response;
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException("Unable to fetch users from group: ".$groupId);
        }
    }

    /**
     * Function to remove user from group by ID using the Barco API
     *
     * @param $groupId
     * @param $userId
     * @return bool ()
     * @throws InvalidResourceException
     */
    public function removeUserFromGroupById($groupId, $userId) {
        $this->log("Request removal of Barco UserId: $userId to GroupId: $groupId");

        try {
            $this->http->delete($this->barcoAPIURL . $this->barcoGroup . "/" . $groupId. "/users/" .$userId , $this->headerOptions);
            $this->log("Barco UserId: $userId has been removed to Barco GroupId: $groupId");
            return true;
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException("Unable to fetch users from group: ".$groupId);
        }
    }

    /**
     * Function to add user to group by ID using the Barco API
     *
     * @param $groupId
     * @param $barco_user_id
     * @return bool ()
     * @throws InvalidResourceException
     */
    public function addUserToGroup($groupId, $barco_user_id) {
        $this->log("Adding User: $barco_user_id to Group: $groupId");

        try {
            $parameters = ['userId' => $barco_user_id];

            $this->headerOptions['json'] = $parameters;
            $response = $this->http->post($this->barcoAPIURL . $this->barcoGroup . "/$groupId/users", $this->headerOptions);
            $response = $response->getBody()->getContents();
            $response = json_decode((string) $response, true);

            return $response;
        } catch (Exception $e){
            $this->log("Error adding User: $barco_user_id to Group: $groupId. ".print_r($e->getMessage(), true));
            throw new InvalidResourceException("Unable to add user to group");
        }
    }

    /**
     * Function to add new userGroup using the Barco API
     *
     * @param $groupName
     * @return string
     * @throws InvalidResourceException
     */
    public function addNewUserGroup($groupName) {
        $this->log("Creating new barco userGroup: $groupName");

        try {
            $parameters = [
                'name' => [
                    'full' => $groupName,
                    'label'=> $groupName
                ],
                'rights' => [],
                'userIdentifiers' => []
            ];

            $this->headerOptions['json'] = $parameters;
            $response = $this->http->post($this->barcoAPIURL . $this->barcoGroup, $this->headerOptions);
            $response = $response->getBody()->getContents();
            $response = json_decode((string) $response, true);

            return $response;
        } catch (Exception $e){
            $this->log("Error creating new group: $groupName".print_r($e->getMessage(), true));
            throw new InvalidResourceException("Error creating new group: $groupName");
        }
    }

    /**
     * Function to delete user group by ID using the Barco API
     * @param $groupId
     *
     * @return array()
     * @throws InvalidResourceException
     */
    public function deleteUserGroupById($groupId) {
        $this->log('Deleting Barco Group with ID: ' . $groupId);

        try {
            $response = $this->http->delete($this->barcoAPIURL . $this->barcoGroup . "/" . $groupId, $this->headerOptions);
            $response = $response->getBody()->getContents();
            $response = json_decode((string) $response, true);
            $this->log("Deleted barco User group: $groupId");

            return $response;
        } catch (Exception $e){
            $this->log($e->getMessage());
            throw new InvalidResourceException($e->getMessage());
        }
    }
}
