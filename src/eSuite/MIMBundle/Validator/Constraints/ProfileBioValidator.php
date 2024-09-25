<?php
    namespace esuite\MIMBundle\Validator\Constraints;

    use Symfony\Component\Validator\Constraint;
    use Symfony\Component\Validator\ConstraintValidator;

    class ProfileBioValidator extends ConstraintValidator
    {
        public function validate($bio, Constraint $constraint): void
        {
            if ( str_word_count((string) $bio) > 300 ) {
                $this->context->addViolation( $constraint->message );
            }
        }

    }
