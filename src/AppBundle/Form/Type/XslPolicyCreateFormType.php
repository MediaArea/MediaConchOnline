<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class XslPolicyCreateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('policyType', 'choice', array('choices' => array('AND' => 'and', 'OR' => 'or'),
                'choices_as_values' => true,
                'placeholder' => false,
                'required' => false,
                'label' => 'Choose a policy type')
                )

            ->add('CreatePolicy', 'submit', array('attr' => array('class' => 'btn-warning')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getName()
    {
        return 'xslPolicyCreate';
    }
}
