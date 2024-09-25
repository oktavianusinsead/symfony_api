<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\Request;

use Insead\MIMBundle\Entity\User;

use Insead\MIMBundle\Exception\ResourceNotFoundException;

class UserAgreementManager extends Base
{
    /**
     * Handler function to update the "agreement" field of a selected user
     *
     * @param Request $request Expects Header parameters
     * @param String $peoplesoftId peoplesoft Id of the user to be updated
     *
     * @return User
     * @throws ResourceNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateUserAgreement(Request $request, $peoplesoftId)
    {
        $this->log("Updating Agreement field of User " . $peoplesoftId);

        $em = $this->entityManager;
        $user = $em
            ->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peoplesoftId]);

        if(!$user) {
            $this->log('User not found');
            throw new ResourceNotFoundException('User not found');
        }

        $agreement = $request->get('agreement');

        if( $agreement === false || $agreement === true ) {
            $user->setAgreement( $agreement );
            if( $agreement ) {
                $user->setAgreementDate(new \DateTime());
            } else {
                $user->setAgreementDate(null);
            }
            $em->persist( $user );
            $em->flush();

            $this->log( "Agreement field of User " . $peoplesoftId . " has been updated to " . $agreement );
        }

        return $user;
    }
}
