<?php

namespace esuite\MIMBundle\Service\Manager;

use Exception;
use Firebase\JWT\JWT;
use esuite\MIMBundle\Entity\Course;

use esuite\MIMBundle\Exception\InvalidResourceException;

use Symfony\Component\HttpFoundation\Request;


class UtilityManager extends Base
{
    /**
     * List of all Roles
     *
     * @var array()
     */
    public $ROLE_ENUM_PLURAL;

    /**
     * Name of the Entity
     *
     * @var string
     */
    public static $courseEntity = "Course";

    protected $uploadDir;
    protected $rootDir;
    protected $edot_api_id;
    protected $edot_api_secret;

    public function loadServiceManager(CoursePeopleManager $coursePeopleManager, $config)
    {
        $this->ROLE_ENUM_PLURAL = $coursePeopleManager::$ROLE_ENUM_PLURAL;

        $this->rootDir          = $config["kernel_root"];
        $this->uploadDir        = $config["upload_temp_folder"];
        $this->edot_api_id     = $config["edot_api_id"];
        $this->edot_api_secret = $config["edot_api_secret"];
    }

    /**
     * @return array
     * @throws InvalidResourceException
     */
    public function recyclePeople(Request $request)
    {

        $this->log("ACTIVITY: recyclePeople");

        $fromCourse     = $request->get('fromCourse');
        $toCourse       = $request->get('toCourse');
        $peopleCategory = $request->get('peopleCategory');

        if (!$fromCourse || !$toCourse || !$peopleCategory){
            $this->log('recyclePeople: Missing parameter');
            throw new InvalidResourceException('Missing parameter');
        }

        if (!is_array($peopleCategory)){
            $this->log('recyclePeople: Missing category');
            throw new InvalidResourceException('Missing category');
        }

        if (count($peopleCategory) === 0){
            $this->log('recyclePeople: Missing people category');
            throw new InvalidResourceException('Missing people category');
        }

        $this->log("Recycle people from course ".$fromCourse." to ".$toCourse." with roles: ".json_encode($peopleCategory));

        /** @var Course $fromCourseObj */
        $fromCourseObj = $this->entityManager
            ->getRepository('esuite\MIMBundle\Entity\\'.self::$courseEntity)
            ->findOneBy(['id' => $fromCourse]);

        if (!$fromCourseObj){
            $this->log('recyclePeople: Course not found');
            throw new InvalidResourceException('Course not found');
        }

        $fromCourseObj->getProgramme()->setOverriderReadonly(true);
        $allUsers = $fromCourseObj->getAllUsers();

        $cleanedPeople = [];
        foreach($peopleCategory as $roleID){
            if (isset($this->ROLE_ENUM_PLURAL[$roleID])) {
                array_push($cleanedPeople, ['people' => $allUsers[$this->ROLE_ENUM_PLURAL[$roleID]], 'role' => $roleID, 'courseID' => $toCourse]);
            } else {
                $this->log('recyclePeople: Invalid people category ('.$roleID.')' );
            }
        }

        return $cleanedPeople;
    }

    /**
     * Handler function to convert image to base64
     *
     * @return array
     */
    public function imageToBase64(Request $request)
    {
        $uploadedFile = $request->files->get('upload');
        if ($uploadedFile !== null) {
            $filename = $uploadedFile->getClientOriginalName();
            $fileType = $uploadedFile->getClientMimeType();
            $fileSize = filesize($uploadedFile);
            $allowedTypes = ["image/png", "image/jpeg"];

            if (!in_array($fileType, $allowedTypes)) {
                $this->log("Invalid file extension. Only allowed file is PNG and JPG: " . $filename. " Uploaded file type: ".$fileType);
                return ["uploaded" => 0, "error" => ["message" => "Invalid file extension. Only allowed file is PNG and JPG"]];
            }

            if ($fileSize > 0) {
                $uploadedFile->move($this->getDocumentUploadDir(), $filename);
                [$width, $height] = getimagesize($this->getDocumentUploadDir() . "/" . $filename);
                if ($width > 300 || $height > 300) {
                    return ["uploaded" => 0, "error" => ["message" => "The image that you have uploaded is $width x $height pixel. Required size is 300 x 300 pixel. Please resize and try again."]];
                } else {
                    $file_content = file_get_contents($this->getDocumentUploadDir() . "/" . $filename);
                    $this->log("Content: " . $file_content);
                    $imgData = base64_encode($file_content);

                    return ['fileName' => $filename, 'uploaded' => 1, 'url' => 'data:' . $fileType . ';base64,' . $imgData];
                }
            } else {
                $this->log("File size is zero");
                return ["uploaded" => 0, "error" => ["message" => "File size is zero."]];
            }
        } else {
            $this->log("Unable to get uploaded file");
            return ["uploaded" => 0, "error" => ["message" => "Invalid file."]];
        }
    }

    /**
     * Generates access to token access edot endpoints
     * @param $upn
     * @return string
     * @throws Exception
     */
    public function generateAccessTokenForUser($upn){
        $tokenExpiry = new \DateTime();
        $tokenExpiry->add(new \DateInterval('PT86400S'));

        $tokenInfo  = ["expiration"    => $tokenExpiry->getTimestamp(), "edot_api_id"  => $this->edot_api_id, "upn"           => $upn];

        $key = $this->edot_api_secret;

        return JWT::encode($tokenInfo,$key, 'HS256');
    }

    /**
     *   Function that returns the temp upload directory where uploaded documents
     *   are saved on disc before passing them on to Box API
     *
     **/
    public function getDocumentUploadDir()
    {
        // real path returns false in some instances, so this has been commented as it should not be necessary
        return $this->rootDir . '/' . $this->uploadDir . '/';
    }

}
