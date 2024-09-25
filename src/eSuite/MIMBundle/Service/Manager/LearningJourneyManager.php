<?php

namespace esuite\MIMBundle\Service\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use esuite\MIMBundle\Entity\Course;
use esuite\MIMBundle\Entity\CourseSubscription;
use esuite\MIMBundle\Service\BoxManager\BoxManager;
use esuite\MIMBundle\Service\edotCourseBackup as Backup;
use esuite\MIMBundle\Service\edotNotify;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use esuite\MIMBundle\Entity\Programme;
use esuite\MIMBundle\Entity\LearningJourney;

use esuite\MIMBundle\Exception\InvalidResourceException;
use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Service\S3ObjectManager;


use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class LearningJourneyManager extends Base
{
    protected S3ObjectManager $s3;
    protected string $rootDir;
    protected string $uploadDir;

    
    protected string $secret;
    protected LoginManager $login;

    protected static $UPLOAD_FILE_TYPES = ['pdf'];

    public function loadServiceManager(S3ObjectManager $s3, $config )
    {
        $this->s3        = $s3;
        $this->rootDir   = $config["kernel_root"];
        $this->uploadDir = $config["upload_temp_folder"];
    }
  
    protected $env;
    /**
     *  @var string
     *  Name of the Entity
     */
    public static string $ENTITY_NAME = "LearningJourney";
    
    /**
     * Function to retrieve Learning Journey for a given Programme
     *
     * @param $programmeId
     * @return array
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    public function getLearningJourney(Request $request, $programmeId,$userId, $scope)
    {

        $em = $this->entityManager;
        $this->log('Programme'.$programmeId);
        /** @var Programme $programme */
        $programme = $em
            ->getRepository(Programme::class)
            ->findOneBy(['id' => $programmeId]);

        if(!$programme) {
            $this->log('Programme not found');
            throw new ResourceNotFoundException('Programme [' . $programmeId . '] was not found');
        }
       
       
        if( $scope == 'edotssvc' || $scope=='edotsvc' ) {
            $programme->setOverriderReadonly(true);
        }

        $programme->setRequestorId($userId);
        $programme->setIncludeHidden(true);
        $programme->setRequestorScope($scope);
        

        $this->checkReadWriteAccessToProgramme($request,$programme,true);
        $learningJourney = $programme->getLearningJourney();
        if($learningJourney) {
            $result = $this->s3->generateSignedURLForLearningJourney("My_Learning_Journey-" . $programme->getId() . ".pdf");
            if ($result) {
                return ["learning-journey" => $result];
            } else {
                return ["learning-journey" => ""];
            }
            
        } else {
            return ["learning-journey" => ""];
        }
    }


    /**
     * Function to update an existing LearningJourney
     *
     * @param Request $request Request Object
     * @param $programmeId
     * @return array
     *
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    public function updateLearningJourney(Request $request, $programmeId)
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

        $this->log('Programme '.$programmeId);

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
                "learning-journey/My_Learning_Journey-" . $programme->getId() . ".pdf",
                fopen($filePath, 'rb'),
                true,
                $this->s3->backupBucket
            );
        } else {
            $this->log("No file uploaded");
        }

        $this->checkReadWriteAccessToProgramme($request,$programme);

        $result = $this->s3->generateSignedURLForLearningJourney($programme->getId() . ".pdf");
        return ["learning-journey" => $result];
    }

    private function getDocumentUploadDir()
    {
        // real path returns false in some instances, so this has been commented as it should not be necessary
        return $this->rootDir . '/' . $this->uploadDir . '/';
    }
}
