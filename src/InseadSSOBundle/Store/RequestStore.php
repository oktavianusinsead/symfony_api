<?php
namespace InseadSSOBundle\Store;

use Insead\MIMBundle\Service\Redis\Saml as RedisSaml;
use LightSaml\State\Request\RequestState;
use LightSaml\Store\Request\RequestStateStoreInterface;
use Psr\Log\LoggerInterface;

class RequestStore implements RequestStateStoreInterface
{
    public function __construct(private readonly LoggerInterface $logger, private readonly RedisSaml $redisSaml)
    {
    }

    /**
     * @param RequestState $state
     *
     * @return RequestStateStoreInterface
     */
    public function set(RequestState $state): RequestStateStoreInterface
    {
        $idEntryObj = [
            "id" => $state->getId(),
            "state" => serialize($state)
        ];

        $json = json_encode($idEntryObj);

        $this->redisSaml->setIdEntry($state->getId(), $json);

        $this->logger->info("ID >>>> ".$state->getId());
        return $this;
    }

    /**
     * @param string $id
     *
     * @return RequestState|null
     */
    public function get($id)
    {
        $this->logger->info("ID get >>>> ".$id);
        $requestEntry = $this->redisSaml->getIdEntry($id);
        if ($requestEntry !== null) {
            $state = json_decode($requestEntry);
            return unserialize($state->state);
        }

        return null;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function remove($id): bool
    {
        $requestEntry = $this->redisSaml->getIdEntry($id);
        if (null === $requestEntry) {
            return false;
        }

        $this->redisSaml->deleteIdEntry($id);

        return true;
    }

    public function clear(): void
    {
    }
}
