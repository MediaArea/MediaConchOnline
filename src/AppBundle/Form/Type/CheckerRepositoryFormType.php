<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckerRepositoryFormType extends CheckerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('check', 'submit', array('attr' => array('class' => 'btn-warning'), 'label' => 'Check files'));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        /*
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Policy',
        ));
        */
    }

    public function getName()
    {
        return 'checkerRepository';
    }
}
