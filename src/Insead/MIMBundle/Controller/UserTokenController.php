<?php

namespace Insead\MIMBundle\Controller;

use Insead\MIMBundle\Entity\UserToken;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Post;
use Insead\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User")]
class UserTokenController extends BaseController
{
    /**
     * @var string
     *  Name of the Entity
     */
    public static string $ENTITY_NAME = "UserToken";

    /**
     * @var string
     *  Name of the User Entity
     */
    public static string $USER_ENTITY_NAME = "User";

    #[Post("/user-tokens/search")]
    #[Allow(["scope" => "studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to search of the owner of a given user-token.")]
    public function getUserTokenSearchAction(Request $request)
    {
        $this->setLogUuid($request);

        $responseObj = [];

        $criterion = $request->get('criterion');
        $tokenBlocks = explode("...",(string) $criterion);

        if( count($tokenBlocks) > 1 ) {
            $firstBlock = $tokenBlocks[0];
            $lastBlock = $tokenBlocks[ count($tokenBlocks)-1 ];

            $result = $this->doctrine
                ->getRepository("Insead\MIMBundle\Entity\\" . self::$ENTITY_NAME)
                ->createQueryBuilder('a')
                ->where('a.oauth_access_token LIKE :first')
                ->andWhere('a.oauth_access_token LIKE :last')
                ->setParameter('first', $firstBlock . '%')
                ->setParameter('last', '%' . $lastBlock)
                ->getQuery()
                ->getResult();

            if( count($result) > 0 ) {

                /** @var UserToken $item */
                foreach( $result as $item) {
                    $tokenRecord = $this->findById(self::$ENTITY_NAME, $item->getId());

                    if( $tokenRecord ) {
                        $user = $this->findById(self::$USER_ENTITY_NAME, $tokenRecord->getUser());

                        $responseObj[] = ['user_id' => $user->getId(), 'peoplesoft_id' => $user->getPeoplesoftId(), 'expiry' => $tokenRecord->getTokenExpiry(), 'last_modified' => $tokenRecord->getUpdated()];
                    }
                }
            }
        }

        return ['users' => $responseObj];
    }
}
