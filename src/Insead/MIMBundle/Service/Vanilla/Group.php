<?php

namespace Insead\MIMBundle\Service\Vanilla;

use http\Encoding\Stream;
use Psr\Log\LoggerInterface;
use Exception;
use Insead\MIMBundle\Exception\VanillaGenericException;
use Symfony\Component\HttpFoundation\Response;

class Group extends Base
{
    protected $url;

    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config,$logger);

        $this->url            = $this->apiUrl . '/groups';
    }

    /**
     * Function to get group detail
     * @param String $groupId
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function getGroupDetails($groupId) {
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];

            $response = $this->http->get($this->url.'/'.$groupId, $options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $this->log("Error occurred while creating Vanilla Group: ".json_encode($e->getMessage()));
            throw new VanillaGenericException($e->getCode(), 'Could not create Vanilla Group: ');
        }

        return $response;
    }

    /**
     * Function to create groups
     * @param Array $groupInfo
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function create($groupInfo) {
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];
            $options['json'] = $groupInfo;
            $response = $this->http->post($this->url, $options);
            $response = $response->getBody()->getContents();
            $this->log("New Vanilla group created: ".$groupInfo['name']);
        } catch (Exception $e) {
            $this->log("Error occurred while creating Vanilla Group: ".json_encode($e->getMessage()));
            throw new VanillaGenericException($e->getCode(), 'Could not create Vanilla Group: ');
        }

        return $response;
    }

    /**
     * Function to list groups
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return array()
     */
    public function list() {
        $this->log('Getting all groups ' . $this->env);

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {
            $response = $this->http->get($this->url,$options);
            $response = $response->getBody()->getContents();

            $response = json_decode((string) $response,true);
        } catch (Exception $e) {
            $this->log("Error occurred while getting list of groups");
            throw new VanillaGenericException($e->getCode(), 'Could not retrieve list of groups');
        }

        return $response;
    }

    /**
     * Function to delete a group
     * @param String $groupId
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function delete($groupId) {
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];

            $response = $this->http->delete($this->url.'/'.$groupId, $options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $this->log("Error occurred while deleting Vanilla Group: ".$groupId);
            throw new VanillaGenericException($e->getCode(), 'Could not delete Vanilla Group');
        }

        return $response;
    }

    /**
     * Function to update a group
     * @param $groupId
     * @param $info
     * @return \Psr\Http\Message\ResponseInterface|string
     * @throws VanillaGenericException
     */
    public function update($groupId, $info) {
        $this->log('Updating group');

        $options['headers'] = ["Authorization" => $this->authHeader];

        try {
            $options['json'] = $info;
            $response = $this->http->patch($this->url.'/'.$groupId,$options);
            $response = $response->getBody()->getContents();

            return $response;
        } catch (Exception $e) {
            $log = "Error occurred while updating group: ".$groupId;
            $this->log($log);
            throw new VanillaGenericException($e->getCode(), $log);
        }
    }

    /**
     * Function to add member to a group
     * @param String $groupId
     * @param Array $userInfo
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function addMember($groupId,$userInfo) {
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];

            $options['json'] = $userInfo;
            $response = $this->http->post($this->url.'/'.$groupId.'/members', $options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $this->log("Error occurred while adding vanilla user id: ".$userInfo['userID']." to group id: ".$groupId);
            throw new VanillaGenericException($e->getCode(), "Error occurred while adding vanilla user id:: ".$userInfo['userID']." to group id: ".$groupId);
        }

        return $response;
    }

    /**
     * Function to get all member of a group
     * @param String $groupId
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function getMember($groupId) {
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];

            $response = $this->http->get($this->url.'/'.$groupId.'/members', $options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $log = "Error occurred while getting member of vanilla group id: ".$groupId;
            $this->log($log);
            throw new VanillaGenericException($e->getCode(), $log);
        }

        return $response;
    }

    /**
     * Function to remove a member of a group
     * @param String $groupId
     * @param String $memberId
     *
     * @return Response
     */
    public function removeMember($groupId, $memberId) {
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];

            $response = $this->http->delete($this->url.'/'.$groupId.'/members/'.$memberId, $options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $log = "Error occurred while removing member of vanilla group id: ".$groupId;
            $log.= "\r\n".$e->getMessage();
            $this->log($log);
            $response = true;
        }

        return $response;
    }

    /**
     * Function to list applicant from a group
     * @param String $groupId
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function applicantList($groupId) {
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];

            $response = $this->http->get($this->url.'/'.$groupId.'/applicants/', $options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $log = "Error occurred while getting the list of applicant from group id: ".$groupId;
            $this->log($log);
            throw new VanillaGenericException($e->getCode(), $log);
        }

        return $response;
    }

    /**
     * Function to approve | deny an applicant from a group
     * @param String $groupId           Group ID
     * @param String $status            Status
     * @param String $vanillaUserId     Vanilla User ID
     *
     * @throws VanillaGenericException if errors occur when communicating with VanillaForums API
     *
     * @return Response
     */
    public function applicantApproval($groupId, $vanillaUserId, $status) {
        try {
            $options['headers'] = ["Authorization" => $this->authHeader];

            $options['json'] = ["status" => $status];

            $response = $this->http->patch($this->url.'/'.$groupId.'/applicants/'.$vanillaUserId, $options);
            $response = $response->getBody()->getContents();
        } catch (Exception $e) {
            $log = "Error occurred while approving applicant('.$vanillaUserId.') with status('.$status.') from group id: ".$groupId;
            $this->log($log);
            throw new VanillaGenericException($e->getCode(), $log);
        }

        return $response;
    }
}
