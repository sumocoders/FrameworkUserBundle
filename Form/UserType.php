<?php

namespace SumoCoders\FrameworkUserBundle\Form;

use FOS\UserBundle\Form\Type\RegistrationFormType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends RegistrationFormType
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'sumocoders_frameworkuserbundle_user';
    }
}
