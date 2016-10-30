<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use AppBundle\Lib\XslPolicy\XslPolicyFormFields;

class XslPolicyRuleMtFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', array('label' => 'Rule name', 'required' => false))
            ->add('field', 'text')
            ->add('validator', 'choice', array('placeholder' => false, 'choices' => XslPolicyFormFields::getOperators(), 'required' => false))
            ->add('value', 'text', array('required' => false))
            ->add('scope', 'hidden', array('data' => 'mmt'))

            ->add('SaveRule', 'submit', array('label' => 'Save', 'attr' => array('class' => 'btn-warning')))
            ->add('DuplicateRule', 'submit', array('label' => 'Duplicate', 'attr' => array('class' => 'btn-warning')))
            ->add('DeleteRule', 'submit', array('label' => 'Delete', 'attr' => array('class' => 'btn-danger')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getName()
    {
        return 'xslPolicyRuleMt';
    }
}
