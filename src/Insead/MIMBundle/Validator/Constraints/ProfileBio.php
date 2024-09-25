<?php
    namespace Insead\MIMBundle\Validator\Constraints;

    use Symfony\Component\Validator\Constraint;


    class ProfileBio  extends Constraint
    {
        public $message = 'Profile Bio should not exceed 350 words in count';

        public function validatedBy(): string
        {
            return static::class.'Validator';
        }

    }
