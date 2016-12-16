<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class XslPolicyCreateFromFileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', 'file', array(
                'label' => 'File (max ' . ini_get('upload_max_filesize') . ')',
                'constraints' => array(new File(array('maxSize' => ini_get('upload_max_filesize')))),
                'attr' => array('data-file-max-size' => ini_get('upload_max_filesize'))))
            ->add('CreatePolicyFromFile', 'submit', array('attr' => array('class' => 'btn-warning')));
    }

    public function getName()
    {
        return 'xslPolicyCreateFromFile';
    }
}
