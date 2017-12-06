<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageCustomType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'preferred_choices' => array('en_GB', 'fr', 'de', 'it', 'sv', 'nl', 'en_US', 'es'),
            'placeholder' => 'Choose your language',
            'required' => false,
        ));
    }

    public function getParent()
    {
        return LanguageType::class;
    }

    public function getBlockPrefix()
    {
        return 'language_custom';
    }
}
