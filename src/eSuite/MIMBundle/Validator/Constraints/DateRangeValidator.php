<?php

namespace esuite\MIMBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use esuite\MIMBundle\Entity\Session;
use esuite\MIMBundle\Entity\Activity;
use esuite\MIMBundle\Entity\Group;

use Psr\Log\LoggerInterface;

/**
 * Date Range Validator service
 * This Works together with esuite\MIMBundle\Annotations\Validator\DateRange Annotation to validate
 * Whether start_date is before end_date in entities
 *
 * @returns bool
 */
class DateRangeValidator extends ConstraintValidator
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function validate($entity, Constraint $constraint): bool
    {
        $this->logger->info('INSIDE DateRangeValidator->isValid()');
        $hasEndDate = true;

        if ($constraint->hasEndDate !== null) {
            $hasEndDate = $constraint->hasEndDate;
        }

        if ($entity->getStartDate() !== null) {
            if ($hasEndDate) {
                if ($entity->getEndDate() !== null) {
                    if ($entity->getStartDate() > $entity->getEndDate()) {
                        $this->context->addViolation($constraint->message, ['%string%' => $entity::class] );
                        return false;
                    }
                    $validated = true;
                } else {
                    $this->context->addViolation($constraint->emptyEndDate, ['%string%' => $entity::class] );
                    return false;
                }
            } else {
                if ($entity->getEndDate() !== null) {
                    if ($entity->getStartDate() > $entity->getEndDate()) {
                        $this->context->addViolation($constraint->message, ['%string%' => $entity::class] );
                        return false;
                    }
                }
                $validated = true;
            }
        } else {
            $this->context->addViolation($constraint->emptyStartDate, ['%string%' => $entity::class] );
            return false;
        }

        // Check if Session/Activity start & end dates are within Course dates
        if( ($entity instanceof Session) || ($entity instanceof Activity) || ($entity instanceof Group) ) {
            $this->logger->info('Creating/Updating Session/Activity');

            if($entity->getStartDate() == $entity->getEndDate()) {
                $this->context->addViolation($constraint->invalidDuration, ['%string%' => $entity::class] );
                return false;
            }

            $course = $entity->getCourse();
            //Check if start_date of Session/Activity is after of just at start_date of Course
            if( $entity->getStartDate() < $course->getStartDate() ||
                $entity->getStartDate() > $course->getEndDate() ||
                $entity->getEndDate() > $course->getEndDate() ) {

                $this->logger->info('Not within Course Date range !!');
                $this->context->addViolation($constraint->datesBeyondModuleDates, ['%string%' => $entity::class] );
            }
        }

        return $validated;
    }
}
