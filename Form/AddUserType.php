<?php

namespace SumoCoders\FrameworkUserBundle\Form;

use FOS\UserBundle\Form\Type\RegistrationFormType;
use Symfony\Component\Form\FormBuilderInterface;

class AddUserType extends RegistrationFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sumocoders_frameworkuserbundle_user';
    }
}
