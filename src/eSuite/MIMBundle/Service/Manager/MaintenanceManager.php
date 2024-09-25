<?php

namespace esuite\MIMBundle\Service\Manager;

use DateTime;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use http\Exception\InvalidArgumentException;
use esuite\MIMBundle\Entity\Organization;
use esuite\MIMBundle\Entity\User;
use esuite\MIMBundle\Entity\UserProfile;
use esuite\MIMBundle\Entity\UserProfileCache;
use esuite\MIMBundle\Entity\UserToken;
use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\PermissionDeniedException;
use esuite\MIMBundle\Exception\SessionTimeoutException;
use esuite\MIMBundle\Service\AIPService;
use esuite\MIMBundle\Service\Redis\AuthToken;
use esuite\MIMBundle\Service\Redis\Maintenance;
use Psr\Log\LoggerInterface;
use esuite\MIMBundle\Service\edotNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use esuite\MIMBundle\Service\BoxManager\BoxManager;
use esuite\MIMBundle\Service\edotCourseBackup as Backup;
use esuite\MIMBundle\Service\S3ObjectManager;
use esuite\MIMBundle\Service\Redis\Base as RedisMain;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use esuite\MIMBundle\Exception\ResourceNotFoundException;


class MaintenanceManager extends Base
{
    protected $redisAuthToken;
    protected $redisMaintenance;

    public function loadServiceManager(Maintenance $redisMaintenance, AuthToken $redisAuthToken)
    {
        $this->redisMaintenance = $redisMaintenance;
        $this->redisAuthToken   = $redisAuthToken;
    }


    /**
     * Handler function to get Maintenance details
     * This API endpoint is restricted to edot SUPER only
     *
     * @return mixed
     * @throws Exception
     */
    public function getMaintenance(Request $request)
    {
        $maintenance = $this->redisMaintenance->getMaintenance();
        if (empty($maintenance) || !isset($maintenance)){
            $this->log("Maintenance details not set. Setting to default");
            $this->forceTurnOffMaintenance($request);

            $maintenance = json_encode($maintenance);
        }

        $currentDateTime = new DateTime();
        $currentDateTime->setTimezone(new \DateTimeZone('UTC'));
        $currentDateTime = $currentDateTime->format('Y-m-d H:i:s');

        $maintenance = json_decode((string) $maintenance, true);

        $maintenance['id']                  = 1;
        $maintenance['enable']              = (array_key_exists('enable', $maintenance) && $maintenance['enable'] == 1);
        $maintenance['date_specific']       = (array_key_exists('date_specific', $maintenance) &&  $maintenance['date_specific'] == 1);
        $maintenance['current_server_time'] = $currentDateTime;

        return ['maintenances' => $maintenance];
    }

    /**
     * Handler function to update Maintenance details
     * This API endpoint is restricted to edot SUPER only
     *
     * @return mixed
     * @throws InvalidResourceException
     * @throws Exception
     */
    public function updateMaintenance(Request $request)
    {
        $this->log("Setting maintenance details");

        $enable          = $request->get('enable');
        $date_specific   = $request->get('date_specific');
        $message         = $request->get('message');
        $start_date      = $request->get('start_date');
        $end_date        = $request->get('end_date');

        $error_message = [];

        $currentMaintenance = $this->getMaintenance($request);

        if ($enable) {
            $this->log("Enabling maintenance");
            $enable = 1;
            $date_specific = 0;
        } else {
            $enable = 0;
            if ($date_specific) {
                $date_specific = 1;
                $this->log("Enabling maintenance with date range");
            } else {
                $date_specific = 0;
                $this->log("Disabling maintenance");
            }
        }

        if (!isset($message) || empty($message)){
            $this->log("Message is missing setting the old message");
            $message = $currentMaintenance['maintenances']['message'];
        }

        if ($date_specific == 1) {
            /*** Check Start Date ***/
            if (!isset($start_date) || empty($start_date)) {
                $this->log("Start Date is missing setting the old Start Date");
                $start_date = $currentMaintenance['maintenances']['start_date'];
            }

            if (DateTime::createFromFormat('Y-m-d H:i:s', $start_date) === FALSE) {
                $this->log("Start Date ($start_date) is not a valid date. Format: Y-m-d H:i:s");
                array_push($error_message, "Start Date is not a valid date. Format: Y-m-d H:i:s");
            }

            /*** Check End Date ***/
            if (!isset($end_date) || empty($end_date)) {
                $this->log("End Date is missing setting old End Date");
                $end_date = $currentMaintenance['maintenances']['end_date'];
            }

            if (DateTime::createFromFormat('Y-m-d H:i:s', $end_date) === FALSE) {
                $this->log("End Date ($end_date) is not a valid date. Format: Y-m-d H:i:s");
                array_push($error_message, "End Date is not a valid date. Format: Y-m-d H:i:s");
            }

            $currentDateTime = new DateTime();
            $start_date_dt = new DateTime($start_date);
            $end_date_dt = new DateTime($end_date);

            if ($currentDateTime >= $start_date_dt) {
                $this->log("Current date (" . $currentDateTime->format('Y-m-d H:i:s') . ") should not be greater than or equal to Start Date ($start_date)");
                array_push($error_message, "Current date (" . $currentDateTime->format('Y-m-d H:i:s') . ") should not be greater than or equal to Start Date ($start_date)");
            }

            if ($start_date_dt >= $end_date_dt) {
                $this->log("Start date ($start_date) should not be greater than or equal to End Date ($end_date)");
                array_push($error_message, "Start date ($start_date) should not be greater than or equal to End Date ($end_date)");
            }

            $start_date = $start_date_dt->format('Y-m-d H:i:s');
            $end_date = $end_date_dt->format('Y-m-d H:i:s');
        }

        if (count($error_message) > 0){
            throw new InvalidResourceException($error_message);
        }

        $maintenance = [
            'enable'        => $enable,
            'date_specific' => $date_specific,
            'message'       => $message,
            'start_date'    => $start_date,
            'end_date'      => $end_date
        ];
        $this->setMaintenance($maintenance);

        return $this->getMaintenance($request);
    }

    /**
     * Handler function if maintenance is needed to show
     * @return bool
     * @throws Exception
     */
    public function isMaintenance(Request $request){
        $maintenance = $this->getMaintenance($request);
        if (!empty($maintenance) && isset($maintenance)) {
            $authToken = $request->headers->get('Authorization');
            if ($authToken) {
                /** if url is authenticated and has bearer token */
                $scope = $request->getSession()->get('scope');

                if (str_contains($authToken, 'Basic')) {
                    $token = trim(substr($authToken, strlen('Basic'), strlen($authToken)));
                    $basicDigest = explode(':', base64_decode($token));
                    $scope = $basicDigest[0];
                }

                if ($scope !== "edotsuper") {
                    /** if authenticated person is not edotsuper  */
                    $enable         = $maintenance['maintenances']['enable'];
                    $date_specific  = $maintenance['maintenances']['date_specific'];
                    $message        = $maintenance['maintenances']['message'];
                    $start_date     = $maintenance['maintenances']['start_date'];
                    $end_date       = $maintenance['maintenances']['end_date'];

                    if ($enable == 1){
                        return true;
                    } else {
                        if ($date_specific == 1){
                            if (DateTime::createFromFormat('Y-m-d H:i:s', $start_date) === FALSE) {
                                $this->log("End Date ($start_date) is not a valid date. Format: Y-m-d H:i:s");
                                return false;
                            }

                            if (DateTime::createFromFormat('Y-m-d H:i:s', $end_date) === FALSE) {
                                $this->log("End Date ($end_date) is not a valid date. Format: Y-m-d H:i:s");
                                return false;
                            }

                            $start_date_dt = new DateTime($start_date);
                            $end_date_dt = new DateTime($end_date);

                            $currentDateTime = new DateTime();
                            $currentDateTime->setTimezone(new \DateTimeZone('UTC'));

                            $this->log("edot@esuite Date Specific enabled Start date: ".$start_date_dt->format("Y-m-d H:i:s")." End date: ".$end_date_dt->format("Y-m-d H:i:s")." Current Date: ".$currentDateTime->format("Y-m-d H:i:s"));
                            if ($currentDateTime >= $start_date_dt && $currentDateTime <= $end_date_dt){
                                $this->log("edot@esuite is currently on Maintenance");
                                return true;
                            } else {
                                if ($currentDateTime < $start_date_dt){
                                    return false;
                                } else {
                                    $this->log("Disabling Maintenance from back end");
                                    $maintenance = [
                                        'enable' => 0,
                                        'date_specific' => 0,
                                        'message' => $message,
                                        'start_date' => $start_date,
                                        'end_date' => $end_date
                                    ];
                                    $this->setMaintenance($maintenance);
                                    return false;
                                }
                            }
                        } else {
                            return false;
                        }
                    }
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Handler function to force turn of Maintenance
     * @return bool
     * @throws Exception
     */
    public function forceTurnOffMaintenance(Request $request){
        $currentDateTime = new DateTime();

        $maintenance = [
            'enable'        => 0,
            'date_specific' => 0,
            'message'       => 'edot@esuite is in Maintenance mode',
            'start_date'    => $currentDateTime->format('Y-m-d H:i:s'),
            'end_date'      => $currentDateTime->format('Y-m-d H:i:s')
        ];

        $this->setMaintenance($maintenance);
        return true;
    }

    /**
     * Handler function to update Maintenance details
     *
     * @param $maintenanceDetails
     * @return mixed
     */
    private function setMaintenance($maintenanceDetails)
    {
        $this->log("Setting maintenance Enabled: ".$maintenanceDetails['enable']." Start: ".$maintenanceDetails['start_date']." End: ".$maintenanceDetails['end_date']);
        $this->redisMaintenance->setMaintenance(json_encode($maintenanceDetails));
    }
}
