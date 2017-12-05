<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Lib\XslPolicy\XslPolicyFormFields;

class XslPolicyRuleMtFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, array('label' => 'Rule name', 'required' => false))
            ->add('field', TextType::class)
            ->add('validator', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => XslPolicyFormFields::getOperators(),
                'required' => false,
                'choices_as_values' => true,
            ))
            ->add('value', TextType::class, array('required' => false))
            ->add('scope', HiddenType::class, array('data' => 'mmt'))

            ->add('SaveRule', SubmitType::class, array('label' => 'Save', 'attr' => array('class' => 'btn-warning')))
            ->add('DuplicateRule', SubmitType::class, array('label' => 'Duplicate', 'attr' => array('class' => 'btn-warning')))
            ->add('DeleteRule', SubmitType::class, array('label' => 'Delete', 'attr' => array('class' => 'btn-danger')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getBlockPrefix()
    {
        return 'xslPolicyRuleMt';
    }
}
