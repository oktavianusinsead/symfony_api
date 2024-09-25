<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\Exception\NotSupported;
use Insead\MIMBundle\Entity\UserToken;

class TokenManager extends Base
{
    private static string $BEARER_HEADER   = 'Bearer';

    /**
     *  Function to create a Temporary Service Token for user checks if user's Token is valid
     *
     * @param String $accessToken Access Token used by the requester
     * @param string $peopleSoftId
     *
     * @return bool
     * @throws NotSupported
     */
    public function validateTokenWithPeopleSoft(string $accessToken, string $peopleSoftId): bool
    {
        if(str_starts_with($accessToken, self::$BEARER_HEADER)) {
            $accessToken = trim(substr($accessToken, strlen(self::$BEARER_HEADER), strlen($accessToken)));
        } else {
            return false;
        }

        /** @var UserToken $userToken */
        $userToken = $this->entityManager
            ->getRepository(UserToken::class)
            ->findOneBy(['oauth_access_token' => $accessToken]);

        if($userToken) {
            $user = $userToken->getUser();
            if ($user->getPeoplesoftId() === $peopleSoftId) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
