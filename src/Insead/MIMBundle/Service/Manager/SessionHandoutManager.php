<?php

namespace Insead\MIMBundle\Service\Manager;

use Exception;

use Insead\MIMBundle\Entity\Session;

use Insead\MIMBundle\Exception\ResourceNotFoundException;

use Symfony\Component\HttpFoundation\Request;


class SessionHandoutManager extends Base
{
    /**
     * Function to retrieve the timestamp of the latest published handout within a given session
     *
     * @param Request $request request object sent to the controller
     * @param integer $sessionId id of the session
     *
     * @throws Exception
     * @throws ResourceNotFoundException
     *
     * @return array
     */
    public function getLatestSessionHandout(Request $request, $sessionId)
    {
        $scope = $this->getCurrentUserScope($request);
        $user = $this->getCurrentUserObj($request);

        /** @var Session $session */
        $session = $this->entityManager
            ->getRepository(Session::class)
            ->findOneBy(['id' => $sessionId]);

        if(!$session) {
            $this->log('Session not found');
            throw new ResourceNotFoundException('Session not found');
        }

        $session->serializeFullObject(TRUE);
        $session->checkGroupSessionAttachmentsFor($user->getPeoplesoftId());
        $session->setSerializeOnlyPublishedAttachments();

        $programme = $session->getCourse()->getProgramme();
        $programme->setRequestorId($user->getId());
        $programme->setForParticipant(true);
        $programme->setIncludeHidden(true);
        if( $scope == "studysuper" ) {
            $programme->setRequestorScope($scope);
        }

        $session->serializeOnlyPublished(TRUE);

        //trigger get session attachment so that it would process setLatestHandout
        $session->getAttachmentList();

        return ["session-handout" => ["session_id" => $sessionId, "latest_handout_delta" => $session->getLatestHandoutPublishAt()]];
    }
}
