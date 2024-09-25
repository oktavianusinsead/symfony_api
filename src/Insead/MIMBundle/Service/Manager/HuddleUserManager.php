<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\ORMException;
use Insead\MIMBundle\Entity\CourseSubscription;
use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Entity\UserProfileCache;
use Doctrine\ORM\OptimisticLockException;

use Symfony\Component\HttpFoundation\Request;
use Insead\MIMBundle\Service\Vanilla\User as VanillaUser;
use Doctrine\Common\Collections\Criteria;

class HuddleUserManager extends Base
{
    private $vanillaUser;

    private $vanillaUserLimitRequest = 5;

    public function loadServiceManager(VanillaUser $vanillaUser)
    {
        $this->logger          = $this->getLogger();
        $this->vanillaUser     = $vanillaUser;
    }

    /**
     * Function to periodically process the creation of vanilla user accounts
     *
     * @return array()
     * @throws OptimisticLockException*@throws \Doctrine\ORM\ORMException
     *
     * @throws ORMException
     */
    public function processHuddleUsers()
    {
        // GET:                              300 requests per 1 minute, per IP
        // POST / PUT / PATCH / DELETE:      20 requests per 1 minute, per IP
        $em = $this->entityManager;

        $userToProcess = $em
            ->getRepository(CourseSubscription::class)
            ->createQueryBuilder('cs')
            ->join('cs.programme', 'p')
            ->join('p.courses', 'c')
            ->join('cs.user', 'u')
            ->join('cs.role', 'r')
            ->where('p.withDiscussions = 1')
            ->andWhere('u.vanillaUserId IS NULL or u.vanillaUserRefresh = 1')
            ->select('u.peoplesoft_id, r.name as role')
            ->addGroupBy('u')
            ->orderBy('c.start_date', 'DESC')
            ->setMaxResults($this->vanillaUserLimitRequest)
            ->getQuery()
            ->getResult();

        $objToProcess = [];
        foreach ($userToProcess as $userDetail){
            /** @var User $user */
            $user = $em
                ->getRepository(User::class)
                ->findOneBy(['peoplesoft_id' => $userDetail['peoplesoft_id']]);

            $isAdmin = false;
            if (strtolower((string) $userDetail['role']) === 'coordinator') $isAdmin = true;

            array_push( $objToProcess, ["isAdmin" => $isAdmin, "obj" => $user, "userRole" => strtolower((string) $userDetail['role'])] );
        }

        $processed = [];
        if( count($objToProcess) ) {

            foreach ($objToProcess as $userItem) {

                /** @var User $user */
                $user = $userItem["obj"];

                $userRole = $userItem["userRole"];

                if ($user->getCacheProfile()) {
                    /** @var UserProfileCache $cacheProfile */
                    $cacheProfile = $user->getCacheProfile();
                    $firstName = $cacheProfile->getFirstname();
                    $lastName = $cacheProfile->getLastname();

                    $firstName = ucwords($firstName);
                    $firstName = str_replace(" ", "", $firstName);
                    $firstName = str_replace("-", "", $firstName);

                    $lastName = ucwords($lastName);
                    $lastNameArr = explode(" ", $lastName);
                    $lastName = "";
                    foreach ($lastNameArr as $item) {
                        $lastName = $lastName . substr($item, 0, 1);
                    }

                    $firstName = trim($firstName);
                    $lastName = trim($lastName);
                    if (strlen($firstName) > 0 || strlen($lastName) > 0) {
                        $this->vanillaUser->create(
                            $user->getPeoplesoftId(),
                            $firstName . "_" . $lastName
                        );

                        $vanillaUser = $this->vanillaUser->addProgrammeRole($user->getPeoplesoftId(), $userRole);

                        if (isset($vanillaUser["userID"])) {
                            $user->setVanillaUserId($vanillaUser["userID"]);
                            $user->setVanillaUserRefresh(false);

                            $em->persist($user);
                        }

                        $processed[] = $user->getPeoplesoftId();
                    }
                }
            }

            $em->flush();

        }

        return ["huddle-users"=> $processed];
    }

    /** Function to update information of given users and save the names in the database
     *
     * @param Request $request object sent through the controller
     *
     * @return array()
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function prepareUserInfo( Request $request ) {

        $processed = [];

        $em = $this->entityManager;

        $criteria = new Criteria();
        $expr = $criteria->expr();

        $criteria->where(
            $expr->andX(
                $expr->orX(
                    $expr->isNull("firstname"),
                    $expr->isNull("lastname")
                ),
                $expr->orX(
                    $expr->neq("vanillaUserId",""),
                    $expr->neq("vanillaUserId",null)
                )
            )
        );

        $users = $em->getRepository(User::class)
            ->matching($criteria);

        /** @var User $user */
        foreach( $users as $user) {
            if( !$user->getFirstname() || !$user->getLastname() ) {
                if ($user->getUserProfileCache()) {
                    /** @var UserProfileCache $cacheProfile */
                    $cacheProfile = $user->getUserProfileCache();
                    $user->setFirstname($cacheProfile->getFirstname());
                    $user->setLastname($cacheProfile->getLastname());
                    $user->setVanillaUserRefresh(true);
                    array_push($processed,$user);
                }
            }
        }

        $em->flush();

        return ["processed" => $processed];
    }

    /**
     * To get profile with Vanilla ID on Study
     *
     * @param $curatedUser
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function prepareVanillaUserInfo($curatedUser){
        $em = $this->entityManager;
        
        /** @var User $userObj */
        $userObj = $em
            ->getRepository(User::class)
            ->findOneBy(["peoplesoft_id"=>$curatedUser["peoplesoft_id"]]);

        if( $userObj ) {
            $isNeedRefresh = false;
            if ($userObj->getFirstname() !== $curatedUser["first_name"]){
                $userObj->setFirstname( $curatedUser["first_name"] );
                $isNeedRefresh = true;
            }

            if ($userObj->getLastname() !== $curatedUser["last_name"]){
                $userObj->setLastname( $curatedUser["last_name"] );
                $isNeedRefresh = true;
            }

            if ($isNeedRefresh){
                $this->log("UserInfo Changed for peopleSoft id: ".$curatedUser["peoplesoft_id"]);
                $userObj->setVanillaUserRefresh(true);
                $em->flush();
            }
        }
    }

}
