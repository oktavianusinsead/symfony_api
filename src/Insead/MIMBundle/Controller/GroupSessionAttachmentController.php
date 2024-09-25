<?php

namespace Insead\MIMBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Put;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations\Get;

use Insead\MIMBundle\Attributes\Allow;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Group")]
class GroupSessionAttachmentController extends BaseController
{

    /**
     * @var string
     *  Name of the Entity
     */
    public static $ENTITY_NAME = "GroupSessionAttachment";

    /**
     *  @var string
     *  Name of the key
     */
    public static $EMBER_NAME = "group_session_attachment";

    #[Get("/group-session-attachments/{groupSessionAttachmentId}")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve a GroupSession.")]
    public function getGroupSessionAttachmentAction(Request $request, $groupSessionAttachmentId)
    {
        $this->setLogUuid($request);

        $responseObj = $this->findById(self::$ENTITY_NAME, $groupSessionAttachmentId);
        return ['group_session_attachment' => $responseObj];
    }

    #[Get("/group-session-attachments")]
    #[Allow(["scope" => "studyadmin,studysuper"])]
    #[OA\Response(
        response: 200,
        description: "Handler function to retrieve multiple GroupSessions.")]
    public function getGroupSessionAttachmentsAction(Request $request)
    {
        $this->setLogUuid($request);

        $items = [];
        $ids = $request->get('ids');
        foreach($ids as $id)
        {
            $this->log("GROUP SESSION: ".$id);
            $items[] = $this->findById(self::$ENTITY_NAME, $id);
        }
        return ['group_session_attachment' => $items];
    }
}
