<?php

namespace SumoCoders\FrameworkUserBundle\Form;

use SumoCoders\FrameworkUserBundle\Form\UserType;
use Symfony\Component\Form\FormBuilderInterface;

class OwnUserType extends UserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // make the password field optional, as we don't want to change this
        // everytime we edit our user
        $builder->get('plainPassword')->setRequired(false);
    }
}
