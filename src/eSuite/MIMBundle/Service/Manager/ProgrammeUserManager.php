<?php

namespace esuite\MIMBundle\Service\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Entity\ProgrammeUser;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\User;

use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Exception\InvalidResourceException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ProgrammeUserManager extends Base
{
    /**
     *  @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "ProgrammeUser";

    public static $CORE_GROUP_ROLES_ENUM = [
        '0' => 'director',
        '1' => 'coordinator',
        '2' => 'faculty',
        '3' => 'guest',
        '4' => 'advisor',
        '5' => 'consultant',
        '6' => 'contact',
        '7' => 'manager',
    ];

    /**
     * Function to retrieve possible Core Group List of a Programme
     *
     * @param Request       $request            Request Object
     * @param String        $programmeId        id of the Programme
     *
     * @return array
     */
    public function getProgrammeCoreGroup(Request $request, $programmeId)
    {
        $em = $this->entityManager;

        $allProgUserArr = [];

        $existingProgUserArr = [];
        $row1Cnt = 0;
        $row2Cnt = 0;

        $tempOtherUserArr = [];
        $otherUserArr = [];

        foreach( self::$CORE_GROUP_ROLES_ENUM as $key => $role ) {
            $otherUserArr[ $role ] = [];
        }

        $programme = $em
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        $programmeUsers = $em
            ->getRepository(ProgrammeUser::class)
            ->findBy(['programme' => $programme]);

        $programmeSubscriptions = $programme->getCourseSubscriptions();

        /** @var ProgrammeUser $programmeUser */
        foreach( $programmeUsers as $programmeUser ) {
            if( $programmeUser->getRowIndex() == 1) {
                $row1Cnt = $row1Cnt + 1;
            } else if( $programmeUser->getRowIndex() == 2) {
                $row2Cnt = $row2Cnt + 1;
            }

            array_push( $existingProgUserArr, $programmeUser->getUser() );
        }

        /** @var CourseSubscription $subscription */
        foreach( $programmeSubscriptions as $subscription ) {
            foreach( self::$CORE_GROUP_ROLES_ENUM as $key => $role ) {
                if( $subscription->getRole()->getName() == $role ) {
                    $roleName = $subscription->getRole()->getName();
                    $psoftId = $subscription->getUser()->getPeoplesoftId();
                    $userId = $subscription->getUser()->getId();

                    if( !isset($tempOtherUserArr[ $psoftId ]) ) {
                        $tempOtherUserArr[ $psoftId ] = ["id" => $userId, "peoplesoft_id" => $psoftId, "role" => $roleName];
                    }
                }
            }
        }

        foreach( $tempOtherUserArr as $subscription ) {

            $isFound = false;
            foreach( $existingProgUserArr as $user ) {
                if( $user->getPeoplesoftId() == $subscription["peoplesoft_id"] ) {
                    $isFound = true;

                    break;
                }
            }

            if( !$isFound ) {
                array_push(
                    $otherUserArr[ $subscription["role"] ],
                    ["id" => $subscription["id"], "peoplesoft_id" => $subscription["peoplesoft_id"], "role" => $subscription["role"]]
                );
            }
        }

        /** @var User $user */
        foreach( $existingProgUserArr as $user ) {
            if( isset($tempOtherUserArr[ $user->getPeoplesoftId() ]) ) {
                array_push(
                    $allProgUserArr,
                    $tempOtherUserArr[ $user->getPeoplesoftId() ]
                );
            } else {
                array_push(
                    $allProgUserArr,
                    ["id" =>  $user->getId(), "peoplesoft_id" =>  $user->getPeoplesoftId(), "role" => ""]
                );
            }
        }

        foreach( self::$CORE_GROUP_ROLES_ENUM as $key => $role ) {
            $items = $otherUserArr[ $role ];

            $allProgUserArr = array_merge(
                $allProgUserArr,
                $items
            );
        }

        return ["programme-users" =>
        ["id" => $programmeId, "programme" => $programmeId, "row1_count" => $row1Cnt, "row2_count" => $row2Cnt, "list" => $allProgUserArr]];
    }

    /**
     * Function to update Core Group List of a Programme
     *
     * @param Request $request Request Object
     * @param String $programmeId id of the Programme
     *
     * @return Response
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateProgrammeCoreGroup(Request $request, $programmeId)
    {
        $em = $this->entityManager;

        $programme = $em
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme not found');
        }

        $this->checkReadWriteAccessToProgramme($request,$programme);

        $list = $request->get( "core_group" );
        $row1Cnt = $request->get( "row1" );
        $row2Cnt = $request->get( "row2" );

        $this->log("Received list for Programme[" . $programmeId . "]: " . $list);
        $this->log("Rows: " . $row1Cnt . " & " . $row2Cnt );

        $listArr = explode(",",(string) $list);
        $listLen = count($listArr);

        $rows = [];
        if( is_numeric($row1Cnt) ) {
            array_push($rows,$row1Cnt);
        }
        if( is_numeric($row2Cnt) ) {
            array_push($rows,$row2Cnt);
        }

        $criteria = new Criteria();
        $expr = $criteria->expr();

        $criteria->where( $expr->in("peoplesoft_id",$listArr) );

        $users = $em
            ->getRepository(User::class)
            ->matching($criteria);

        $userArr = [];
        /** @var User $user */
        foreach( $users as $user ) {
            $userArr[ $user->getPeoplesoftId() ] = $user;
        }

        //clear existing programme users records
        $programmeUsers = $em
            ->getRepository(ProgrammeUser::class)
            ->findBy(['programme' => $programme]);

        foreach( $programmeUsers as $programmeUser ) {
            $em->remove( $programmeUser );
        }
        $em->flush();

        $validRecords = [];
        $invalidRecords = [];

        //separate invalid items
        for( $i = 0; $i < $listLen; $i++ ) {
            $item = $listArr[$i];
            $user = $userArr[ $item ];

            if($user) {
                array_push( $validRecords, $item );
            } else {
                array_push( $invalidRecords, $item );
            }
        }

        $this->log( "Removing invalid items: " . json_encode($invalidRecords) );

        $validRecordsLen = count($validRecords);
        $rowIndex = 1;
        $orderIndex = 1;

        for( $i = 0; $i < $validRecordsLen; $i++ ) {
            $item = $validRecords[$i];
            $user = $userArr[ $item ];

            if( $rowIndex < 3 ) {
                if ($user) {
                    $programmeUser = new ProgrammeUser();
                    $programmeUser
                        ->setProgramme($programme)
                        ->setOrderIndex($orderIndex)
                        ->setRowIndex($rowIndex)
                        ->setUser($user);

                    $em->persist($programmeUser);

                    $orderIndex = $orderIndex + 1;

                    if ($orderIndex > 3 || $orderIndex > $rows[$rowIndex-1] ) {
                        $rowIndex = $rowIndex + 1;
                        $orderIndex = 1;
                    }
                }
            }
        }

        $em->flush();

        $programmeUsers = $em
            ->getRepository(ProgrammeUser::class)
            ->findBy(['programme' => $programme]);

        return $programmeUsers;
    }

}
