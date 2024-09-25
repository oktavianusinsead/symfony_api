<?php

namespace Insead\MIMBundle\Service\Manager;

use Insead\MIMBundle\Entity\Country;
use Insead\MIMBundle\Entity\States;

class UserManager extends Base
{
    public static $REQUIRED_JOB_FIELDS = ['title', 'organisation', 'address_line_1', 'address_line_2', 'address_line_3', 'country', 'postal_code', 'city'];

    /**
     *  Function to validate Dependent fields
     *
     * @param array  $errors  array of error messages
     * @param array  $data    Data to be validated
     * @param string $field1  field to test
     * @param string $field2  field to test
     * @param string $message error message
     */
    public function validateDependentFields(&$errors, $data, $field1, $field2, $message)
    {
        if (array_key_exists($field1, $data) && !is_null($data[$field1]) && $data[$field1] != "") {

            if (array_key_exists($field2, $data) && !is_null($data[$field2]) && $data[$field2] != "") {
                $isValidPrefix = $this->validatePrefix($data[$field2]);

                if (!$isValidPrefix) {
                    $errors[ 'phone_numbers' ] = [$message];
                }
            }
        } else {
            if (array_key_exists($field2, $data) && !is_null($data[$field2]) && $data[$field2] != "") {
                $errors[ 'phone_numbers' ] = [$message];
            }
        }
    }

    /**
     * Third Party function the converts file size to human readable format
     *
     * reference: http://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes
     */
    public function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     *  Function to check jobfields are mandatory
     *
     * @param array  $data    Data to be checked
     * @param boolean  $job_exist  Flag to determine if there is an existing job
     * @return boolean
     */
    public function checkRequiredFields($data, $job_exist) {
        $flag = false;
        foreach (self::$REQUIRED_JOB_FIELDS as $jobFields) {

            $jobFieldValue = isset($data[$jobFields]) ? trim((string) $data[$jobFields]) : '';
            if (!empty($jobFieldValue)) {
                $flag = true;
            }
        }

        if ($job_exist && !($flag)) {
            $flag = true;
        }

        return $flag;
    }

    /**
     *  Function to validate State
     *
     * @param string $countryCode country code id
     * @param string $stateCode  state code id
     * @return boolean
     */
    public function validateState($countryCode, $stateCode)
    {
        $response = false;

        /** @var Country $country */
        $country = $this->entityManager
            ->getRepository(Country::class)
            ->findOneBy( ['ps_country_code' => $countryCode]);

        /** @var States $state */
        $state = $this->entityManager
            ->getRepository(States::class)
            ->findOneBy( ['ps_state_code' => $stateCode, 'country' => $country]);

        if($state) {
            $response = true;
        }

        return $response;
    }

    /**
     *  Function to check has a State list
     *
     * @param string $countryCode country code id
     * @return boolean
     */
    public function hasState($countryCode)
    {
        $response = false;

        /** @var Country $country */
        $country = $this->entityManager
            ->getRepository(Country::class)
            ->findOneBy( ['ps_country_code' => $countryCode]);

        /** @var States $state */
        $state = $this->entityManager
            ->getRepository(States::class)
            ->findOneBy( ['country' => $country]);

        if($state) {
            $response = true;
        }

        return $response;
    }

    /**
     *  Function to get the country name
     *
     * @param string $countryCode country code id
     * @return string
     */
    public function getCountryTitle($countryCode)
    {
        /** @var Country $country */
        $country = $this->entityManager
            ->getRepository(Country::class)
            ->findOneBy( ['ps_country_code' => $countryCode]);

        $countryName = $country ? $country->getTitle() : "";

        return $countryName;
    }

    /**
     *  Function to validate prefix phone
     *
     * @param string $prefix phone prefix
     * @return boolean
     */
    public function validatePrefix($prefix)
    {
        $response = false;

        /** @var Country $country */
        $country = $this->entityManager
            ->getRepository(Country::class)
            ->findOneBy( ['phone_code' => $prefix]);

        if($country) {
            $response = true;
        }

        return $response;
    }

    /**
     *  Function to check if there is contact phone
     *
     * @param array  $data    Data to be checked
     * @return boolean
     */
    public function contactPhoneExist($data)
    {
        $flgExist = true;

        if ((is_null($data['personal_phone']) || $data['personal_phone'] == "")
            && (is_null($data['work_phone']) || $data['work_phone'] == "")
            && (is_null($data['cell_phone']) || $data['cell_phone'] == "")
        ) {
            $flgExist = false;
        }

        return $flgExist;
    }
}
