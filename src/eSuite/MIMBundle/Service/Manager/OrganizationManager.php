<?php

namespace esuite\MIMBundle\Service\Manager;


use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Entity\Organization;
use esuite\MIMBundle\Exception\InvalidResourceException;
use Symfony\Component\HttpFoundation\Request;

class OrganizationManager extends Base
{

    /**
     * Handler for receiving the list of organization from ESB.
     * This is used by ESB to push updated/new profiles
     *
     *
     * @return mixed
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidResourceException
     */
    public function receiveOrganization(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if (!$payload){
            $this->log("[ESB] No content / Unable to parse content for organization. \r\n[ESB - payload] ".json_encode($request->getContent()));
            throw new InvalidResourceException('[ESB] No content / Unable to parse content for organization.');
        } else {
            if (!array_key_exists('organizations', $payload)) {
                $this->log("[ESB] organizations key not existing. \r\n[ESB - payload] ".json_encode($request->getContent()));
                throw new InvalidResourceException('[ESB] organizations key not existing.');
            } else {
                if (!array_key_exists('correlationKey', $payload)) {
                    $this->log("[ESB] correlationKey key not existing. \r\n[ESB - payload] ".json_encode($request->getContent()));
                    throw new InvalidResourceException('[ESB] correlationKey key not existing.');
                } else {
                    $reply['correlationKey'] = $payload['correlationKey'];
                    $reply['organizations'] = [];

                    $list_organization_obj = $payload['organizations'];
                    foreach ($list_organization_obj as $organization_obj) {

                        if (!array_key_exists('ext_org_id', $organization_obj) || strlen((string) $organization_obj['ext_org_id']) < 1) {
                            $reply['organizations'][] = [
                                "id" => "",
                                "success" => false,
                                "title" => $organization_obj['title'],
                                "errorMessage" => "Missing ext_org_id ID key",
                                "status" => "failed",
                            ];
                        } else {
                            if (!array_key_exists('title', $organization_obj) || strlen((string) $organization_obj['title']) < 1) {
                                $reply['organizations'][] = [
                                    "id" => $organization_obj['ext_org_id'],
                                    "success" => false,
                                    "title" => "",
                                    "errorMessage" => "Missing title key",
                                    "status" => "failed",
                                ];
                            } else {
                                $edot_org = $this->entityManager
                                    ->getRepository(Organization::class)
                                    ->findOneBy(['ext_org_id' => trim((string) $organization_obj['ext_org_id'])]);

                                $status = "updated";
                                if (!$edot_org) {
                                    $edot_org = new Organization();
                                    $status = "created";
                                }
                                $this->setOrganizationValues($edot_org, $organization_obj);

                                $this->entityManager->persist($edot_org);

                                $reply['organizations'][] = [
                                    "id" => $edot_org->getExtOrgId(),
                                    "success" => true,
                                    "title" => $edot_org->getTitle(),
                                    "status" => $status
                                ];
                            }
                        }
                    }

                    $this->entityManager->flush();

                    return $reply;
                }
            }
        }

    }

    /**
     * Setter for Organization entity values
     * @param Organization $organization_obj
     * @param array $organization
     */
    public function setOrganizationValues(&$organization_obj, $organization){
        $organization_obj->setExtOrgId(trim((string) $organization['ext_org_id']));
        $organization_obj->setTitle(trim((string) $organization['title']));
    }
}
