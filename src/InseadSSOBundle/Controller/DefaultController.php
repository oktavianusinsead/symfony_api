<?php

namespace InseadSSOBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Insead\MIMBundle\Entity\User;
use Insead\MIMBundle\Entity\UserProfileCache;
use Insead\MIMBundle\Exception\PermissionDeniedException;
use Insead\MIMBundle\Service\Manager\Base as ManagerBase;
use Insead\MIMBundle\Service\Manager\CoursePeopleManager;
use Insead\MIMBundle\Service\Manager\MaintenanceManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Session as PHPSession;
use Insead\MIMBundle\Controller\BaseController as BaseController;
use FOS\RestBundle\Controller\Annotations\Route;
use Insead\MIMBundle\Service\Redis\Saml as RedisSaml;
use Insead\MIMBundle\Service\Manager\LoginManager as LoginManager;
use Insead\MIMBundle\Service\Manager\UtilityManager as UtilityManager;

use Insead\MIMBundle\Service\Redis\AuthToken as RedisAuthToken;
use Insead\MIMBundle\Service\Redis\Maintenance as RedisMaintenance;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DefaultController extends BaseController
{
    private readonly ParameterBagInterface $pBag;

    public function __construct(ParameterBagInterface $parameterBag,
                                ManagerBase $base,
                                protected RedisSaml $redisSaml,
                                protected LoginManager $loginManager,
                                protected UtilityManager $utilityManager,
                                LoggerInterface $logger,
                                protected MaintenanceManager $maintenanceManager,
                                RedisAuthToken $redisAuthToken,
                                RedisMaintenance $redisMaintenance,
                                CoursePeopleManager $coursePeopleManager,
                                ManagerRegistry $doctrine,
                                protected TokenStorageInterface $tokenStorage) {
        parent::__construct($logger, $doctrine, $parameterBag, $base);
        $this->pBag           = $parameterBag;
        $this->loginManager->loadServiceManager($redisAuthToken, $parameterBag->get('acl.config'));
        $this->utilityManager->loadServiceManager($coursePeopleManager, $parameterBag->get('utility.config'));
        $this->maintenanceManager->loadServiceManager($redisMaintenance, $redisAuthToken);
        $this->logger         = $logger;
    }

    public function getParameterBag(): ParameterBagInterface
    {
        return $this->pBag;
    }

    #[Route("/", methods: ["GET", "POST"])]
    public function indexAction(Request $request): RedirectResponse|Response
    {
        $session = $request->getSession();
        $session->remove('sso_scope');
        $samlAllItems = $this->getSAMLAllItems();
        $samlAllItems = array_change_key_case($samlAllItems, CASE_UPPER);
        $userScope    = 'studyweb';

        if (array_key_exists('SCOPE', $samlAllItems)) $userScope = $samlAllItems['SCOPE'];
        if (array_key_exists('scope', $samlAllItems)) $userScope = $samlAllItems['scope'];

        if (!$userScope) {
            $this->log("Current scope: studyweb");
        } else {
            $this->log("Current scope from query params: ".$userScope);
        }

        $errorLogOutUrl = "../api/v1.2/slologout";

        if ($userScope) {
            switch ($userScope) {
                case 'studyadmin':
                    $redirectToURL = $this->pBag->get('study_adminurl').'/token?';
                    $appUrl = $this->pBag->get('study_adminurl');
                    $appTitle = "Study@INSEAD Admin";
                    $showLogOutButton = true;
                    break;
                case 'studyios':
                    $redirectToURL = $request->getSchemeAndHttpHost().$this->generateUrl('ios_transient_key').'?';
                    $appUrl = false;
                    $appTitle = "";
                    $showLogOutButton = false;
                    break;
                default:
                    $redirectToURL = $this->pBag->get('study_weburl').'/token?';
                    $appUrl = $this->pBag->get('study_weburl');
                    $appTitle = "Study@INSEAD";
                    $showLogOutButton = true;
            }
        } else {
            $redirectToURL = $this->pBag->get('study_weburl').'/token?';
            $appUrl = $this->pBag->get('study_weburl');
            $appTitle = "Study@INSEAD";
            $showLogOutButton = true;
        }

        $userScope = $this->validateScope( $userScope );
        $userScope = $this->loginManager->isSuperScope($userScope, $samlAllItems['INSEADPSOFTID']);

        if (!array_key_exists('INSEADPSOFTID', $samlAllItems) || strlen((string) $samlAllItems['INSEADPSOFTID']) < 1) {
            $this->log("SSO:Unauthorised user tries to access the app");
            $UnAuthorisedUserMsg = 'You do not have permission to access the app.';

            return $this->render(
                '@InseadSSO/ssoerror.html.twig',
                ['msg'            => $UnAuthorisedUserMsg, 'showContact'    => true, 'showlogout'     => $showLogOutButton, 'errorLogOutUrl' => $errorLogOutUrl, 'webappUrl'      => $appUrl, 'appTitle'       => $appTitle]
            );
        }

        try{
            // validate user scope
            $this->loginManager->matchScopeWithUserRole($samlAllItems['INSEADPSOFTID'], $userScope);
        } catch (PermissionDeniedException $exception) {
            $UnAuthorisedUserMsg = $exception->getMessage();

            return $this->render(
                '@InseadSSO/ssoerror.html.twig',
                ['msg'         => $UnAuthorisedUserMsg, 'showContact' => true, 'showlogout'  => $showLogOutButton, 'errorLogOutUrl' => $errorLogOutUrl, 'webappUrl'   => $appUrl, 'appTitle'    => $appTitle]
            );
        }
        $upn = $this->getUPNFromCache($samlAllItems['INSEADPSOFTID']);

        if (!$upn) {
            $this->log("SSO:Unauthorised user tries to access the app");
            $UnAuthorisedUserMsg = 'You do not have permission to access the app.';

            return $this->render(
                '@InseadSSO/ssoerror.html.twig',
                ['msg'         => $UnAuthorisedUserMsg, 'showContact' => true, 'showlogout'  => $showLogOutButton, 'errorLogOutUrl' => $errorLogOutUrl, 'webappUrl'   => $appUrl, 'appTitle'    => $appTitle]
            );
        }

        if ($userScope !== "studysuper"){
            $maintenance = $this->maintenanceManager->getMaintenance($request);
            if (!empty($maintenance)){ /** if Maintenance is set  */
                if ($maintenance['maintenances']['enable'] === 1) {
                    return new Response($this->renderView(
                        '@MIM/maintenance.html.twig',
                        ['msg' => $maintenance['maintenances']['message']]
                    ),200, ['Content-Type' => 'text/html; charset=UTF-8']);
                }
            }
        }

        $redisSaml = $this->redisSaml;
        $accessToken = $this->utilityManager->generateAccessTokenForUser($upn);

        /*
         * access_token =  token to use for Study API connection
         * expires_in   = 7 days, before token expires, unless user opted to log out
         */
        $data = ['data' => [
            'access_token'  => $accessToken,
            'expires_in'    => 604800,
            'token_type'    => 'Bearer',
            'refresh_token' => $redisSaml->generateRandomToken( $accessToken . mktime(0)),
            'session_index' => $samlAllItems['SESSIONINDEXID']
        ]];

        $result = $this->prepareResponseData( $data["data"], $samlAllItems['INSEADPSOFTID'], $userScope, $request->getSession() );

        if ($result["success"] === false) {
            return $this->render(
                '@InseadSSO/ssoerror.html.twig',
                ['msg'         => $result["error"], 'showContact' => true, 'showlogout'  => $showLogOutButton, 'errorLogOutUrl' => $errorLogOutUrl, 'webappUrl'   => $appUrl, 'appTitle'    => $appTitle]
            );
        }

        $now = new \DateTime();
        $timeFormat = $now->format('Y-m-d H:i:s.u');
        $randomToken = $redisSaml->generateRandomToken( $accessToken . $timeFormat );
        $this->log("Random token for time format: ".$timeFormat." upn: ".$upn);
        $redisSaml->setUserTempInfo($randomToken,json_encode($result['data']));

        $parameters = http_build_query(["transient-key" => $randomToken]);

        /** @var User $user */
        $user = $this->doctrine
            ->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $samlAllItems['INSEADPSOFTID']]);
        if ($user){ //Update last log in date
            $user->setLastLoginDate($now);
            $this->doctrine->getManager()->persist($user);
            $this->doctrine->getManager()->flush();
        }

        $response = new RedirectResponse($redirectToURL . $parameters);
        $response->headers->clearCookie('scope');

        return $response;
    }

    private function validateScope( $userScope ) {
        $validScopes        = ['studystudent', 'studysvc', 'studyssvc', 'studysuper', 'studyadmin'];
        $isValidScope       = $userScope && in_array($userScope, $validScopes);

        // validate scope
        if (!$isValidScope) {
            $userScope = 'studystudent';
        }

        return $userScope;
    }

    private function getEncodedSAMLResponse(){
        $token              = $this->tokenStorage->getToken();

        $sessionIndex       = $token->getAttribute('saml_session_index');

        $jsonData = $this->redisSaml->getIdEntry($sessionIndex);

        return json_decode($jsonData,true);
    }

    private function getSAMLAllItems(){
        $data = $this->getEncodedSAMLResponse();

        $allItems = $data["saml_response_all_items"];

        $this->log("SSO:SAMLResponse all items retrieved from ADFS => ".print_r($allItems, true));

        return $allItems;
    }

    private function getAgreement( $peoplesoft_id ) {
        $userStatus = $this->doctrine
            ->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peoplesoft_id]);

        return $userStatus->agreement;
    }

    /**
     * Getting upn from cache profile
     * @param $peopleSoftID of the logged in user
     *
     * @return bool|string
     */
    private function getUPNFromCache($peopleSoftID){
        /** @var User $user */
        $user = $this->doctrine
            ->getRepository(User::class)
            ->findOneBy(['peoplesoft_id' => $peopleSoftID]);

        if ($user) {
            if ($user->getUserProfileCache()) {
                /** @var UserProfileCache $userCache */
                $userCache = $user->getUserProfileCache();
                if ($userCache->getUpnEmail()) {
                    return $userCache->getUpnEmail();
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Handler for preparing the application tokens
     *
     * @param $data
     * @param $peopleSoftID
     * @param $userScope
     * @param $session
     * @return array
     * @throws Exception
     */
    private function prepareResponseData( $data, $peopleSoftID, $userScope, $session ) {
        // calculate expiry date
        $tokenExpiry = new \DateTime();
        $tokenExpiry->add(new \DateInterval('PT' . $data['expires_in'] . 'S'));

        $this->log("SSO::recording token information to the DB");
        // create mim session entity
        $authenticationData = ['access_token'   => $data['access_token'], 'refresh_token'  => $data['refresh_token'], 'token_expiry'   => $tokenExpiry, 'peoplesoft_id'  => $peopleSoftID, 'scope'          => $userScope, 'session_index'  => $data['session_index']];
        $this->log("Prepping Auth data: ".print_r($authenticationData, true));
        $userToken = $this->loginManager->persistUserTokens($authenticationData, null, $userScope);

        // change access token to mim auth token
        $authenticationData['access_token'] = $userToken->getOauthAccessToken();

        // change expiry date to 8601
        $authenticationData['token_expiry'] = $tokenExpiry->format(\DateTime::ISO8601);

        $authenticationData['agreement'] = $this->getAgreement( $authenticationData['peoplesoft_id'] );

        $this->saveTokenToSession( $authenticationData, $session );

        return ["data" => $authenticationData, "success" => true];
    }

    /**
     * Saves Token to PHP Session
     *
     * @param array         $data       array object that contains the user information
     * @param PHPSession    $session    PHP Session
     */
    private function saveTokenToSession( $data, $session ) {
        $this->log("SSO::caching oauth token to application-session");
        // Save oauth_access_token to session for oauth logout reference
        $session->set('oauth_access_token', $data['access_token']);
        $session->save();
    }
}
