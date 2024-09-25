<?php

namespace esuite\MIMBundle\Tests\Mock;

use esuite\MIMBundle\Entity\LearningJourney;

class LearningJourneyMock extends LearningJourney
{
    public function setId($id)
    {
        $this->id = $id;
    }
}