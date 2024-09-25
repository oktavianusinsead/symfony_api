<?php

namespace Insead\MIMBundle\Service\Vanilla;

use phpDocumentor\Reflection\Types\String_;
use Psr\Log\LoggerInterface;

use Insead\MIMBundle\Service\Redis\Vanilla as Redis;

class Role extends Base
{
    private $participantLabel;
    private $adminLabel;

    private $INSEADTeamLabel;
    private $INSEADContactLabel;

    protected $url;

    public function __construct(array $config, LoggerInterface $logger, private readonly Redis $redis)
    {
        parent::__construct($config,$logger);

        $this->participantLabel     = "Participant";
        $this->adminLabel           = "BizAdmin";

        //Improved grouping of roles
        $this->INSEADTeamLabel      = "INSEAD Team"; // Coordinators, Faculty, Advisor, Consultants, Directors, Management
        $this->INSEADContactLabel   = "Contact";

        $this->url                  = $this->apiUrl . '/roles';
    }

    /**
     * Function to get available roles in VanillaForums
     *
     * @return array()
     */
    public function getRoles() {
        $this->log('Retrieving VANILLA roles');

        $options['headers'] = ["Authorization" => $this->authHeader];

        $response = $this->http->get($this->url, $options);
        $response = $response->getBody()->getContents();

        $response = json_decode((string) $response,true);

        return $response;
    }

    /**
     * Function to get participant role in VanillaForums
     *
     * @return array()
     */
    public function getParticipantRole() {
        $participantRole = [];

        $tempRole = $this->redis->getRoleId( $this->participantLabel );

        if( $tempRole ) {
            $participantRole = [$tempRole];

        } else {
            foreach( $this->getRoles() as $role ) {
                if( isset($role['roleID']) && isset($role['name']) ) {
                    if( $role['name'] == $this->participantLabel ) {
                        array_push($participantRole, $role['roleID'] );

                        $this->redis->setRoleId( $this->participantLabel, $role['roleID'] );

                        break;
                    }
                }
            }
        }

        return $participantRole;
    }


    /**
     * Function to get admin role in VanillaForums
     *
     * @return array()
     */
    public function getAdminRole() {
        $adminRole = [];

        $tempRole = $this->redis->getRoleId( $this->adminLabel );

        if( $tempRole ) {
            $adminRole = [$tempRole];

        } else {
            foreach( $this->getRoles() as $role ) {
                if( isset($role['roleID']) && isset($role['name']) ) {
                    if( $role['name'] == $this->adminLabel ) {
                        array_push( $adminRole, $role['roleID'] );

                        $this->redis->setRoleId($this->adminLabel,$role['roleID']);

                        break;
                    }
                }
            }
        }

        return $adminRole;
    }

    /**
     * Function to get role in VanillaForums by role in study
     * @param String $studyRole Role of the user
     *
     * @return array()
     */
    public function getVanillaRole($studyRole) {
        $facultyRole = [];
        $vanillaLabel = $this->participantLabel;

        switch ($studyRole){
            case "coordinator":
            case "faculty":
            case "advisor":
            case "consultant":
            case "director":
            case "manager":
                $vanillaLabel = $this->INSEADTeamLabel;
                break;

            case "contact":
            case "guest":
                $vanillaLabel = $this->INSEADContactLabel;
                break;

        }

        $tempRole = $this->redis->getRoleId( $vanillaLabel );

        if( $tempRole ) {
            $facultyRole = [$tempRole];

        } else {
            foreach( $this->getRoles() as $role ) {
                if( isset($role['roleID']) && isset($role['name']) ) {
                    if( $role['name'] == $vanillaLabel ) {
                        array_push( $facultyRole, $role['roleID'] );

                        $this->redis->setRoleId($vanillaLabel,$role['roleID']);

                        break;
                    }
                }
            }
        }

        return $facultyRole;
    }

}
