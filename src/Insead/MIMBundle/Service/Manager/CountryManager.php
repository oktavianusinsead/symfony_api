<?php

namespace Insead\MIMBundle\Service\Manager;

use Doctrine\ORM\Exception\NotSupported;
use Insead\MIMBundle\Entity\Country;
use Insead\MIMBundle\Entity\States;

use Insead\MIMBundle\Exception\ResourceNotFoundException;

use Symfony\Component\HttpFoundation\Request;

class CountryManager extends Base
{
    /**
     * If database has no record, call MyInsead API to get Countries
     *
     * @return array
     * @throws NotSupported
     */
    public function getCountries()
    {
        /** @var Country $fromCourseObj */
        $countries = $this->entityManager
            ->getRepository(Country::class)
            ->createQueryBuilder('c')
            ->getQuery()
            ->getResult();

        $mimFormat = ['countries' => []];
        $idx = 0;
        $idd=0;
        /** @var Country $country */
        foreach ($countries as $country) {
            $mimFormat['countries'][$idx]['code'] = $country->getPsCountryCode();
            $mimFormat['countries'][$idx]['name'] = $country->getTitle();
            $mimFormat['countries'][$idx]['nationality'] = $country->getNationality();
            $mimFormat['countries'][$idx]['phone_code'] = $country->getPhoneCode();
            $states = $country->getStates();

            $cnt = 0;
            if (count($states) > 0) {

                /** @var States $state */
                foreach ($states as $state) {
                    $mimFormat['countries'][$idx]['states'][$cnt] = [
                        'id'=>$idd++,
                        'ps_state_code' => $state->getStateCode(),
                        'state_name' => $state->getStateName(),
                        'country_id' =>$country->getPsCountryCode()
                    ];
                    $cnt++;
                }
            } else {
                $mimFormat['countries'][$idx]['states'][$cnt] = null;
            }

            $idx++;
        }

        return $mimFormat;
    }

    /**
     * Get all the list of States
     *
     * @return array
     * @throws NotSupported
     */
    public function getStatesList()
    {

        /** @var States $fromCourseObj */
        $states = $this->entityManager
            ->getRepository(States::class)
            ->createQueryBuilder('s')
            ->getQuery()
            ->getResult();

        $mimFormat = ['states' => []];

        if (count($states) > 0) {
            $idx = 0;

            /** @var States $state */
            foreach ($states as $state) {
                $mimFormat['states'][$idx]['state_name'] = $state->getStateName();
                $mimFormat['states'][$idx]['ps_state_code'] = $state->getStateCode();
                $country = $state->getCountry();
                $mimFormat['states'][$idx]['country'] = $country->getPsCountryCode();
                $mimFormat['states'][$idx]['country_name'] = $country->getTitle();
                $idx++;
            }
        }

        return $mimFormat;
    }

}
