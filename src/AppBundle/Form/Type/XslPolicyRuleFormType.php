<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use AppBundle\Lib\XslPolicy\XslPolicyFormFields;

class XslPolicyRuleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, array('label' => 'Rule name', 'required' => false))

            // Standard editor
            ->add('trackType', ChoiceType::class, array(
                'placeholder' => 'Choose a track type',
                'choices' => XslPolicyFormFields::getTrackTypes(),
            ))
            ->add('field', ChoiceType::class, array('placeholder' => 'Choose a field'))
            ->add('occurrence', IntegerType::class, array('attr' => array('min' => 1), 'required' => false))
            ->add('validator', ChoiceType::class, array(
                'placeholder' => false,
                'choices' => XslPolicyFormFields::getOperators(),
                'required' => false,
            ))
            ->add('value', null, array('label' => 'Content'))
            ->add('scope', HiddenType::class, array('data' => ''))

            ->add('SaveRule', SubmitType::class, array('label' => 'Save', 'attr' => array('class' => 'btn-warning')))
            ->add('DuplicateRule', SubmitType::class, array('label' => 'Duplicate', 'attr' => array('class' => 'btn-warning')))
            ->add('DeleteRule', SubmitType::class, array('label' => 'Delete', 'attr' => array('class' => 'btn-danger')));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $item = $event->getData();
            $form = $event->getForm();

            if ($item && null !== $item->getTrackType()) {
                $form->add('field', ChoiceType::class, array(
                    'placeholder' => 'Choose a field',
                    'choices' => XslPolicyFormFields::getFields($item->getTrackType(), $item->getField()),
                ));
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $item = $event->getData();
            $form = $event->getForm();

            if ($item && isset($item['trackType'])) {
                $form->add('field', ChoiceType::class, array(
                    'placeholder' => 'Choose a field',
                    'choices' => XslPolicyFormFields::getFields($item['trackType'], $item['field']),
                ));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getBlockPrefix()
    {
        return 'xslPolicyRule';
    }
}
