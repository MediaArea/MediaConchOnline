<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisplayImportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('displayName', 'text', array('label' => 'Display name'))
            ->add('displayFile', 'file', array('attr' => array('accept' => '.xsl,.xml'), 'label' => 'Display file'))
            ->add('ImportDisplay', 'submit', array('attr' => array('class' => 'btn-warning')));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getName()
    {
        return 'displayImport';
    }
}
