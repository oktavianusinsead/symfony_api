<?php

namespace Insead\MIMBundle\Service\Redis;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Maintenance extends Base
{
    protected $maintenance_key = 'maintenance_details';

    public function __construct( ParameterBagInterface $parameterBag, LoggerInterface $logger)
    {
        parent::__construct($parameterBag, $logger);
    }

    /**
     * Function to set Maintenance details
     * @param String $maintenance
     **
     * @return mixed
     */
    public function setMaintenance($maintenance) {
        $this->redis->set($this->maintenance_key,$maintenance);
        return $this->getMaintenance();
    }

    /**
     * Function to get Maintenance details
     *
     * @return mixed
     */
    public function getMaintenance() {
        return $this->redis->get($this->maintenance_key);
    }

    /**
     * Function to delete Maintenance details
     *
     * @return mixed
     */
    public function deleteMaintenance() {
        return $this->redis->del($this->maintenance_key);
    }

}
