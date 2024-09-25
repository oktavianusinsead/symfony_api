<?php

namespace Insead\MIMBundle\Tests\Entity;

use Insead\MIMBundle\Tests\Mock\CountryMock;
use PHPUnit\Framework\TestCase;

class CountryTest extends TestCase
{
    public function testCountry()
    {
        $country = new CountryMock();
        $country->setId(12);
        $this->assertEquals(12, $country->getId());

        $country->setPsCountryCode("SGP");
        $this->assertEquals("SGP", $country->getPsCountryCode());

        $country->setTitle("Singapore");
        $this->assertEquals("Singapore", $country->getTitle());

        $country->setNationality("Singaporean");
        $this->assertEquals("Singaporean", $country->getNationality());

        $country->setPhoneCode("65");
        $this->assertEquals("65", $country->getPhoneCode());

        $country->setStates(['Serangoon', 'Bishan']);
        $this->assertEquals(['Serangoon', 'Bishan'], $country->getStates());
    }
}
