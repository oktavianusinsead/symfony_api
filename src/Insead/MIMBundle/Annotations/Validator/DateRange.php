<?php

namespace Insead\MIMBundle\Annotations\Validator;

use Symfony\Component\Validator\Constraint;

/**
 *  Annotation class for @DateRange()
 *  @Annotation
 *  @Target({"CLASS"})
 */
class DateRange extends Constraint {

    public $message = "Start Date/Time should be before End Date/Time.";
    public $emptyStartDate = "Start Date/Time cannot be blank.";
    public $emptyEndDate = "End Date/Time cannot be blank.";
    public $datesBeyondModuleDates = "Sessions, Activities and Sections must be scheduled within the date range of a Course.";
    public $invalidDuration = "Duration cannot be 0.";

    public $hasEndDate = true;

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'daterange_validator';
    }
}
