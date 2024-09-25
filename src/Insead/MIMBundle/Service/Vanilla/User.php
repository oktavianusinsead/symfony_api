<?php

namespace Insead\MIMBundle\Service\Vanilla;

use Psr\Log\LoggerInterface;
use Exception;
use Insead\MIMBundle\Exception\VanillaGenericException;

use Insead\MIMBundle\Service\Vanilla\Role;

class User extends Base
{
    protected $url;
    protected $role;

    public function __construct(array $config, LoggerInterface $logger, Role $role)
    {
        parent::__construct($config,$logger);

        $this->url            = $this->apiUrl . '/users';
        $this->role           = $role;
    }

    /**
     * Function to create a user using the VanillaForums API
     * @param String $peopleSoftId peoplesoft id of the user to be added
     * @param String $name name/label for the user
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return array()
     */
    public function create($peopleSoftId,$name) {
        $this->log('CREATING VANILLA ' . $this->env . ' USER: ' . $peopleSoftId);

        $options = [];
        $options['headers'] = ["Authorization" => $this->authHeader];

        $username = trim((trim(strtoupper((string) $this->env)) == "PRD" ?  "" : strtoupper((string) $this->env)."~"). $peopleSoftId . "~" . $name);

        $prodEmail = $peopleSoftId . "@insead.edu";
        $testEmail = $peopleSoftId."-".strtolower((string) $this->env) . "@insead.edutest";

        $email = (trim(strtoupper((string) $this->env)) == "PRD" ?  $prodEmail : $testEmail);


        $vanillaUser = $this->getUserByPeoplesoftId($peopleSoftId);

        if (isset($vanillaUser["roles"])) {
            $roleIDs = [];
            foreach ($vanillaUser["roles"] as $rolesObj){
                array_push($roleIDs, $rolesObj['roleID']);
            }

            $options['json'] = ["name" => $username, "email" => $email, "password" => $this->generateRandomString(), "emailConfirmed" => true, "bypassSpam" => true, "roleID" => $roleIDs];
        } else {
            $options['json'] = ["name" => $username, "email" => $email, "password" => $this->generateRandomString(), "emailConfirmed" => true, "bypassSpam" => true, "roleID" => $this->role->getParticipantRole()];
        }

        if( !$vanillaUser ) {
            try {
                $response = $this->http->post($this->url, $options);
                $response = $response->getBody()->getContents();

                $vanillaUser = json_decode((string) $response,true);

            } catch (Exception $e) {
                $this->log("Error occurred while creating VanillaUser " . $e->getCode() . " - " . $e->getMessage());
                throw new VanillaGenericException($e->getCode(), 'Could not create Vanilla user.' . ' Error Message: ' . $e->getMessage());
            }
        } else {

            if( $vanillaUser['name'] != $username ) {
                $patchOptions = [
                    'headers' => ["Authorization" => $this->authHeader],

                    'json' => ["name" => $username, "email" => $email]
                ];

                try {
                    $response = $this->http->patch($this->url . "/" . $vanillaUser['userID'] , $patchOptions);
                    $response = $response->getBody()->getContents();
                } catch (Exception $e) {
                    $this->log("Error occurred while creating VanillaUser " . $e->getCode() . " - " . $e->getMessage());
                    throw new VanillaGenericException($e->getCode(), 'Could not create Vanilla user.' . ' Error Message: ' . $e->getMessage());
                }

                $vanillaUser = json_decode((string) $response,true);
            }
        }

        return $vanillaUser;
    }

    /**
     * Function to add admin role to a user using the VanillaForums API
     * @param String $peopleSoftId peopleSoft id of the user to be added
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return array()
     */
    public function addAdminRole($peopleSoftId) {
        $this->log('ATTACHING ADMIN ROLE TO VANILLA USER ' . $this->env . ' USER: ' . $peopleSoftId);

        $vanillaUser = $this->getUserByPeoplesoftId($peopleSoftId);

        if( $vanillaUser ) {

            $roleIDs = [];
            if (isset($vanillaUser["roles"])) {
                foreach ($vanillaUser["roles"] as $rolesObj){
                    array_push($roleIDs, $rolesObj['roleID']);
                }
            }

            $adminRole = $this->role->getAdminRole();
            $isRoleFound = false;

            if (is_array($adminRole)) {
                $adminRoleID = $adminRole[0];

                foreach ($roleIDs as $roleID) {
                    if ($roleID == $adminRoleID) {
                        $isRoleFound = true;
                        break;
                    }
                }
            }

            if (!$isRoleFound){
                $patchOptions = [
                    'headers' => ["Authorization" => $this->authHeader],

                    'json' => ["roleID" => $this->role->getAdminRole()]
                ];

                try {
                    $response = $this->http->patch($this->url . "/" . $vanillaUser['userID'], $patchOptions);
                    $response = $response->getBody()->getContents();
                } catch (Exception $e) {
                    $this->log("Error occurred while attaching admin role to VanillaUser " . $e->getCode() . " - " . $e->getMessage());
                    throw new VanillaGenericException($e->getCode(), 'Could not create Vanilla user.' . ' Error Message: ' . $e->getMessage());
                }

                $vanillaUser = json_decode((string) $response, true);
            }
        }

        return $vanillaUser;
    }

    /**
     * Function to add programme role to a user using the VanillaForums API
     *
     * @param $peopleSoftId peopleSoft id of the user to be added
     * @param $role role of the user in a programme
     * @return array|mixed
     * @throws VanillaGenericException
     */
    public function addProgrammeRole($peopleSoftId, $role) {
        $this->log('ATTACHING '.strtoupper((string) $role).' ROLE TO VANILLA USER ' . $this->env . ' USER: ' . $peopleSoftId);

        $vanillaUser = $this->getUserByPeoplesoftId($peopleSoftId);

        if( $vanillaUser ) {

            $roleIDs = [];
            if (isset($vanillaUser["roles"])) {
                foreach ($vanillaUser["roles"] as $rolesObj){
                    array_push($roleIDs, $rolesObj['roleID']);
                }
            }

            $roleIDtoFind = false;
            $arrayOfRoleIDToFind = $this->role->getVanillaRole($role);
            if (is_array($arrayOfRoleIDToFind)){
                if (count($arrayOfRoleIDToFind) > 0){
                    $roleIDtoFind = intval($arrayOfRoleIDToFind[0]);
                }
            }

            $isRoleFound = false;

            foreach ($roleIDs as $roleID) {
                if ($roleID == $roleIDtoFind) {
                    $isRoleFound = true;
                    break;
                }
            }

            if (!$isRoleFound) {
                if ($roleIDtoFind) {
                    array_push($roleIDs, $roleIDtoFind);
                }

                $patchOptions = [
                    'headers' => ["Authorization" => $this->authHeader],

                    'json' => ["roleID" => $roleIDs]
                ];

                try {
                    $response = $this->http->patch($this->url . "/" . $vanillaUser['userID'], $patchOptions);
                    $response = $response->getBody()->getContents();
                } catch (Exception $e) {
                    $this->log("Error occurred while attaching admin role(".$role.") to VanillaUser " . $e->getCode() . " - " . $e->getMessage());
                    throw new VanillaGenericException($e->getCode(), 'Could not create Vanilla user.' . ' Error Message: ' . $e->getMessage());
                }

                $vanillaUser = json_decode((string) $response, true);
            }
        }

        return $vanillaUser;
    }

    /**
     * Function to get user by Peoplesoft ID using the VanillaForums API
     * @param String $peopleSoftId peoplesoft id of the user to be added
     *
     * @return array()
     */
    public function getUserByPeoplesoftId($peopleSoftId) {
        $this->log('Finding VANILLA ' . $this->env . ' USER: ' . $peopleSoftId);

        $username = trim((trim(strtoupper((string) $this->env)) == "PRD" ?  "" : strtoupper((string) $this->env)."~"). $peopleSoftId . "~");

        $options['headers'] = ["Authorization" => $this->authHeader];

        $response = $this->http->get($this->url . "/by-names?name=" . $username . "*", $options);
        $response = $response->getBody()->getContents();

        $response = json_decode((string) $response,true);

        if( count($response) > 0 ) {
            $vanillaUser = $response[0];

            $response = $this->http->get($this->url . "/" . $vanillaUser['userID'], $options);
            $response = $response->getBody()->getContents();

            $response = json_decode((string) $response,true);
        }
        return $response;
    }

    /** Function to randomly generate a string for password
     *
     * @param int $length of the randomString
     *
     * @return String
     */
    private function generateRandomString($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
