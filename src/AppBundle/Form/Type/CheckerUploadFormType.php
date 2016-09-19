<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CheckerUploadFormType extends CheckerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('file', 'file', array('label' => 'File (max ' . ini_get('upload_max_filesize') . ')', 'constraints' => array(new File(array('maxSize' => ini_get('upload_max_filesize')))), 'attr' => array('data-file-max-size' => ini_get('upload_max_filesize'))))
            ->add('check', 'submit', array('attr' => array('class' => 'btn-warning'), 'label' => 'Check file'));
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
        return 'checkerUpload';
    }
}
