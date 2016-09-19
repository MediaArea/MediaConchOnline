<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class XslPolicyInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('policyName', null, array('required' => false))
            ->add('policyDescription', null, array('required' => false))
            ->add('policyType', 'choice', array('choices' => array('AND' => 'and', 'OR' => 'or'),
                'choices_as_values' => true,
                'placeholder' => false)
                )
            ->add('SavePolicyInfo', 'submit', array('attr' => array('class' => 'btn-warning')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getName()
    {
        return 'xslPolicyInfo';
    }
}
