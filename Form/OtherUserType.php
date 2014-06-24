<?php

namespace SumoCoders\FrameworkUserBundle\Form;

use SumoCoders\FrameworkUserBundle\Form\UserType;
use Symfony\Component\Form\FormBuilderInterface;

class OtherUserType extends UserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // remove the password as we don't want other users to be able to
        // change the password
        $builder->remove('plainPassword');
    }
}
