<?php

namespace Insead\MIMBundle\Service\Manager;

use Psr\Log\LoggerInterface;
use Insead\MIMBundle\Service\StudyNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use Insead\MIMBundle\Service\BoxManager\BoxManager;
use Insead\MIMBundle\Service\StudyCourseBackup as Backup;
use Insead\MIMBundle\Service\S3ObjectManager;

use Insead\MIMBundle\Entity\Programme;

use Insead\MIMBundle\Exception\ResourceNotFoundException;
use Insead\MIMBundle\Exception\InvalidResourceException;

use Symfony\Component\HttpFoundation\Request;


class ProgrammeLearningJourneyManager extends Base
{
    protected $s3;
    protected $rootDir;
    protected $uploadDir;

    protected static $UPLOAD_FILE_TYPES = ['pdf'];

    public function loadServiceManager(S3ObjectManager $s3, $config )
    {
        $this->s3                   = $s3;
        $this->rootDir = $config["kernel_root"];
        $this->uploadDir = $config["upload_temp_folder"];
    }

    /**
     * Function to retrieve Learning Journey display for a given Programme
     *
     * @param $programmeId
     * @param $userId
     * @param $scope
     * @return array
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    public function getProgrammeLearningJourney(Request $request, $programmeId,$userId, $scope)
    {

        $em = $this->entityManager;

        /** @var Programme $programme */
        $programme = $em
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme [' . $programmeId . '] was not found');
        }

        if( $scope == 'studyssvc' || $scope=='studysvc' ) {
            $programme->setOverriderReadonly(true);
        }

        $programme->setRequestorId($userId);
        $programme->setIncludeHidden(true);
        $programme->setRequestorScope($scope);

        $this->checkReadWriteAccessToProgramme($request,$programme,true);
        $learningJourney = $programme->getLearningJourney();
        if($learningJourney) {
            $result = $this->s3->getFromS3(
                "programme-company-logo-inline-style/" . $programme->getId() . ".pdf",
                true
            );

            $this->log("PDF result: " . print_r($result, true));

            if (is_object($result)) {
                return ["PDF" => base64_encode((string) $result['Body'])];
            } else {
                return ["PDF" => ""];
            }
        } else {
            return ["PDF" => ""];
        }
    }

    /**
     * Function to update Learning Journey display for a given Programme
     *
     * @param Request       $request            Request Object
     * @param String        $programmeId        id of the Programme
     *
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     *
     * @return array
     */
    public function updateProgrammeLearningJourney(Request $request, $programmeId)
    {

        $uploadedFile = $request->files->get('file');

        

        $em = $this->entityManager;

        $programme = $em
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme [' . $programmeId . '] was not found');
        }



        if( $uploadedFile ) {
            $filename = $uploadedFile->getClientOriginalName();
            $filePath = $this->getDocumentUploadDir().$filename;

            $uploadedFile->move($this->getDocumentUploadDir(), $filename);

            // Get File extension
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            // Check if it is one of allowed file extension types
            if (!in_array($ext, self::$UPLOAD_FILE_TYPES)) {
                throw new InvalidResourceException('Please select a valid file type. Only PDF files are allowed for Learning Journey.');
            }

            $this->s3->uploadToS3(
                "programme-company-logo/" . $programme->getId() . ".pdf",
                fopen($filePath, 'rb'),
                true
            );
        }

        $this->checkReadWriteAccessToProgramme($request,$programme);

       

        $result = $this->s3->getFromS3(
            "programme-company-logo/" . $programme->getId() . ".pdf",
            true
        );

        // push notification
        $this->notify->setLogUuid($request);
        foreach($programme->getCourses() as $course) {
            $this->notify->message($course, "Learning Journey");
        }

        return ["PDF" => base64_encode( (string) $result['Body'] )];
    }

    private function getDocumentUploadDir()
    {
        // real path returns false in some instances, so this has been commented as it should not be necessary
        return $this->rootDir . '/' . $this->uploadDir . '/';
    }


}
