<?php

namespace Insead\MIMBundle\Service\Manager;

use Insead\MIMBundle\Entity\ArchivedUserToken;
use Insead\MIMBundle\Entity\UserToken;

use Doctrine\Common\Collections\Criteria;

use Exception;


class CronArchiveUserTokenManager extends Base
{
    public function archiveUserTokens()
    {
        $interval = new \DateInterval('P1M');
        $bufferTime = new \DateTime();
        $bufferTime->sub($interval);

        $criteria = new Criteria();
        $expr = $criteria->expr();
        $criteria->where( $expr->lte('created',$bufferTime) );

        $em = $this->entityManager;

        $oldTokens = $em
            ->getRepository(UserToken::class)
            ->matching($criteria);

        $tokensToProcess = [];

        if( count($oldTokens) ) {
            /** @var UserToken $item */
            foreach( $oldTokens as $item ) {
                if( count($tokensToProcess) < 50 ) {
                    array_push( $tokensToProcess, $item );
                }
            }

            /** @var UserToken $userToken */
            foreach( $tokensToProcess as $userToken ) {
                //archive the user token information
                $withRefreshToken = $userToken->getRefreshToken() != "";

                $archivedUserToken = new ArchivedUserToken();
                $archivedUserToken->setUser( $userToken->getUser() )
                    ->setScope( $userToken->getScope() )
                    ->setRefreshable( $withRefreshToken )
                    ->setCreated( $userToken->getUpdated() )
                    ->setUpdatedValue();

                $em->persist($archivedUserToken);

                //Delete UserToken
                $em->remove($userToken);
            }

            $em->flush();
        }

        $oldTokens = $em
            ->getRepository(UserToken::class)
            ->matching($criteria);

        return ["pending-archive"=>"Remaining Token for archiving " . count($oldTokens)];
    }

}
