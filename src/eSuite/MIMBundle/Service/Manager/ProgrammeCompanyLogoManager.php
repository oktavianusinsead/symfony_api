<?php

namespace esuite\MIMBundle\Service\Manager;

use Psr\Log\LoggerInterface;
use esuite\MIMBundle\Service\edotNotify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use JMS\Serializer\Serializer;
use esuite\MIMBundle\Service\BoxManager\BoxManager;
use esuite\MIMBundle\Service\edotCourseBackup as Backup;
use esuite\MIMBundle\Service\S3ObjectManager;

use esuite\MIMBundle\Entity\Programme;

use esuite\MIMBundle\Exception\ResourceNotFoundException;
use esuite\MIMBundle\Exception\InvalidResourceException;

use Symfony\Component\HttpFoundation\Request;


class ProgrammeCompanyLogoManager extends Base
{
    protected $s3;
    protected $rootDir;
    protected $uploadDir;

    protected static $UPLOAD_FILE_TYPES = ['svg'];

    public function loadServiceManager(S3ObjectManager $s3, $config )
    {
        $this->s3                   = $s3;
        $this->rootDir = $config["kernel_root"];
        $this->uploadDir = $config["upload_temp_folder"];
    }

    /**
     * Function to retrieve Company Logo display for a given Programme
     *
     * @param $programmeId
     * @param $userId
     * @param $scope
     * @return array
     * @throws InvalidResourceException
     * @throws ResourceNotFoundException
     */
    public function getProgrammeCompanyLogo(Request $request, $programmeId,$userId, $scope)
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

        if( $scope == 'edotssvc' || $scope=='edotsvc' ) {
            $programme->setOverriderReadonly(true);
        }

        $programme->setRequestorId($userId);
        $programme->setIncludeHidden(true);
        $programme->setRequestorScope($scope);

        $this->checkReadWriteAccessToProgramme($request,$programme,true);
        $companyLogo =$programme->getCompanyLogo();
        if($companyLogo) {
            $result = $this->s3->getFromS3(
                "programme-company-logo-inline-style/" . $programme->getId() . ".svg",
                true
            );

            if (is_object($result)) {
                return ["logo" => base64_encode((string) $result['Body'])];
            } else {
                return ["logo" => ""];
            }
        } else {
            return ["logo" => ""];
        }
    }

    /**
     * Function to update Company Logo display for a given Programme
     *
     * @param Request       $request            Request Object
     * @param String        $programmeId        id of the Programme
     *
     * @throws ResourceNotFoundException
     * @throws InvalidResourceException
     *
     * @return array
     */
    public function updateProgrammeCompanyLogo(Request $request, $programmeId)
    {

        $uploadedFile = $request->files->get('file');

        $companyLogoSize = $request->get("company_logo_size");

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
                throw new InvalidResourceException('Please select a valid file type. Only SVG files are allowed for Company Logo.');
            }

            $this->s3->uploadToS3(
                "programme-company-logo/" . $programme->getId() . ".svg",
                fopen($filePath, 'rb'),
                true
            );
        }

        $this->checkReadWriteAccessToProgramme($request,$programme);

        if ( !is_null($companyLogoSize) ) {
            $programme->setCompanyLogoSize($companyLogoSize);
            $em->persist($programme);
            $em->flush();
        }

        $result = $this->s3->getFromS3(
            "programme-company-logo/" . $programme->getId() . ".svg",
            true
        );

        // push notification
        $this->notify->setLogUuid($request);
        foreach($programme->getCourses() as $course) {
            $this->notify->message($course, "ProgrammeCompanyLogo");
        }

        return ["logo" => base64_encode( (string) $result['Body'] )];
    }

    private function getDocumentUploadDir()
    {
        // real path returns false in some instances, so this has been commented as it should not be necessary
        return $this->rootDir . '/' . $this->uploadDir . '/';
    }


}
