<?php

namespace esuite\MIMBundle\Service\Manager;

use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\FileDocument;
use esuite\MIMBundle\Entity\Link;
use esuite\MIMBundle\Entity\LinkedDocument;
use esuite\MIMBundle\Entity\PendingAttachment;
use esuite\MIMBundle\Entity\Video;

use esuite\MIMBundle\Service\S3ObjectManager;

use Doctrine\Common\Collections\Criteria;

use Exception;


class CronPendingAttachmentManager extends Base
{
    protected $rootDir;
    protected $uploadDir;
    protected $s3;

    public function loadServiceManager(S3ObjectManager $s3, $config)
    {
        $this->s3                   = $s3;
        $this->rootDir = $config["kernel_root"];
        $this->uploadDir = $config["upload_temp_folder"];
    }

    public function processPendingAttachments()
    {
        $criteria = new Criteria();
        $expr = $criteria->expr();
        $criteria->where( $expr->eq('published',false) );

        $em = $this->entityManager;

        $pendingItems = $em
            ->getRepository(PendingAttachment::class)
            ->matching($criteria);

        $courses = [];
        $processedPendingItems = [];
        if( count($pendingItems) ) {

            /** @var PendingAttachment $item */
            foreach( $pendingItems as $item ) {
                /** @var \DateTime $publishedAt */
                $publishedAt = $item->getPublishAt();
                $publishedAt->setTimezone(new \DateTimeZone($item->getSession()->getCourse()->getTimezone()));

                $publishedAt_bufferTime = new \DateTime();
                $publishedAt_bufferTime->setTimezone(new \DateTimeZone($item->getSession()->getCourse()->getTimezone()));
                $publishedAt_bufferTime->add(new \DateInterval('PT1M'));

                if ($publishedAt <= $publishedAt_bufferTime) {
                    $item->setPublished(true);
                    array_push($processedPendingItems, $item);
                    if( !isset( $courses[ $item->getSession()->getCourseId() ] ) ) {
                        $courses[ $item->getSession()->getCourseId() ] = $item->getSession()->getCourse();
                    }

                    $em->persist($item);
                }
            }

            /**
             * @var String $key
             * @var Course $course
             */
            foreach( $courses as $key => $course ) {
                $this->log("Sending notification for course " . $course->getId());
                $this->notify->message($course,"Session");
            }

            $em->flush();
        }

        //get the latest Pending Attachment
        $criteriaLatestItem = new Criteria();
        $criteriaLatestItem->orderBy(
            ["updated" => $criteria::DESC]
        );

        $latestItems = $em
            ->getRepository(PendingAttachment::class)
            ->matching($criteriaLatestItem);

        if( count($latestItems) ) {
            /** @var PendingAttachment $latestItem */
            $latestItem = $latestItems[0];

            $this->checkForPendingAttachments( $latestItem->getUpdated() );
        }

        return ["pending-attachments"=>"Processed " . count($processedPendingItems) . " attachments for " . count($courses) . " course(s)"];
    }

    public function checkForPendingAttachments( \DateTime $timestamp = null )
    {
        $this->log( "Checking for Pending Attachments" );
        $now = new \DateTime();

        $interval = new \DateInterval('P1Y');
        $bufferTime = new \DateTime();
        $bufferTime->add($interval);

        $criteria = new Criteria();
        $expr = $criteria->expr();
        $criteria->where( $expr->gte('publish_at',$now) );
        $criteria->andWhere( $expr->lt('publish_at',$bufferTime) );

        if( $timestamp ) {
            $this->log("Appending records that are added after " . json_encode($timestamp) );
            $criteria->andWhere( $expr->gt('created',$timestamp) );
        }

        $this->savePendingAttachment("FileDocument", $criteria);
        $this->savePendingAttachment("Link", $criteria);
        $this->savePendingAttachment("LinkedDocument", $criteria);
        $this->savePendingAttachment("Video", $criteria);

        $em = $this->entityManager;

        $checkPending = $em
            ->getRepository(PendingAttachment::class)
            ->findBy(["published" => false]);

        $this->log(count($checkPending) . " Pending Attachments in the database");

        return ["pending-attachments" => count($checkPending)];

    }

    private function savePendingAttachment( $modelName, Criteria $criteria ) {
        $em = $this->entityManager;

        try {

            $pendingFileDocuments = $em
                ->getRepository('esuite\MIMBundle\Entity\\' . $modelName)
                ->matching($criteria);

            if (count($pendingFileDocuments)) {
                /** @var FileDocument|Link|LinkedDocument|Video $item */
                foreach ($pendingFileDocuments as $item) {
                    /** @var PendingAttachment $checkPending */
                    $checkPending = $em
                        ->getRepository(PendingAttachment::class)
                        ->findOneBy(["attachment_id" => $item->getId(), "attachment_type" => $item->getAttachmentType()]);

                    if (!$checkPending) {
                        $pendingAttachment = new PendingAttachment();
                        $pendingAttachment->setAttachmentId($item->getId())
                            ->setAttachmentType($item->getAttachmentType())
                            ->setPublishAt($item->getPublishAt())
                            ->setSession($item->getSession());

                        $em->persist($pendingAttachment);
                    } else {
                        if( $checkPending->getPublishAt() != $item->getPublishAt() ) {
                            $checkPending->setPublishAt( $item->getPublishAt() );

                            $em->persist($checkPending);
                        }
                    }
                }

                $em->flush();
            }
        } catch( Exception $e ) {
            $this->log("Error while attempting to capture list of Pending Items for " . $modelName .  " - " . $e->getMessage());
        }
    }
}
