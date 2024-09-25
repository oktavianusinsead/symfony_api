<?php

namespace Insead\MIMBundle\Tests\Mock;

use Insead\MIMBundle\Entity\LearningJourney;

class LearningJourneyMock extends LearningJourney
{
    public function setId($id)
    {
        $this->id = $id;
    }
}