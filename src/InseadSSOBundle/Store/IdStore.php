<?php
namespace InseadSSOBundle\Store;

use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\Redis\Saml as RedisSaml;

use LightSaml\Provider\TimeProvider\TimeProviderInterface;
use LightSaml\Store\Id\IdStoreInterface;

class IdStore implements IdStoreInterface
{
    public function __construct(private readonly LoggerInterface $logger, private readonly RedisSaml $redisSaml, private readonly TimeProviderInterface $timeProvider)
    {
    }

    /**
     * @param string    $entityId
     * @param string    $id
     * @param \DateTime $expiryTime
     *
     * @return void
     */
    public function set($entityId, $id, \DateTime $expiryTime)
    {

        $idEntryObj = [
            "entity_id" => $entityId,
            "id" => $id,
            "expiry_time" => $expiryTime
        ];

        $json = json_encode($idEntryObj);

        $this->redisSaml->setIdEntry($id,$json);
    }

    /**
     * @param string $entityId
     * @param string $id
     *
     * @return bool
     */
    public function has($entityId, $id)
    {
        $idEntryJson = $this->redisSaml->getIdEntry($id);

        $idEntry = json_decode($idEntryJson,true);

        if( isset($idEntry["id"]) && isset($idEntry["entity_id"]) && isset($idEntry["expiry_time"]) ) {
            $this->logger->info("SAML ID Entry is complete. Veriying....");
            $dateObj = $idEntry["expiry_time"];

            if( $dateObj ) {
                $checkDate = date_parse($dateObj["date"] . $dateObj["timezone"]);

                if ($checkDate["error_count"]) {
                    $this->logger->info("SAML Expiry time is not a valid date");
                    return false;
                }

                $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s T', $dateObj["date"] . $dateObj["timezone"]);

                if ($dateTime->getTimestamp() < $this->timeProvider->getTimestamp()) {
                    $this->logger->info("SAML Information has expired");
                    return false;
                }

                //valid date
                if( $idEntry["id"] == $id && $idEntry["entity_id"] == $entityId )  {
                    return true;
                }

            }

        }

        return false;
    }
}
