<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class XslPolicyInfoFormType extends AbstractType
{
    protected $authChecker;

    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('policyName', null, array('required' => false))
            ->add('policyDescription', TextareaType::class, array('required' => false))
            ->add('policyType', ChoiceType::class, array('choices' => array('AND' => 'and', 'OR' => 'or'),
                'choices_as_values' => true,
                'placeholder' => false, )
                )
            ->add('policyLicense', ChoiceType::class, array('choices' => array('Creative Commons Zero' => 'CC0-1.0+', 'Creative Commons Attribution' => 'CC-BY-4.0+', 'Creative Commons Attribution-ShareAlike' => 'CC-BY-SA-4.0+', 'Other' => ''),
                'choices_as_values' => true,
                'placeholder' => false, )
                )
            ->add('policyTopLevel', HiddenType::class);

        if ($this->authChecker->isGranted('ROLE_BASIC')) {
            $builder->add('policyVisibility', ChoiceType::class, array('choices' => array('Private' => false, 'Public' => true),
                'choices_as_values' => true,
                'placeholder' => false, )
                );
        }

        $builder->add('SavePolicyInfo', SubmitType::class, array('attr' => array('class' => 'btn-warning')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getBlockPrefix()
    {
        return 'xslPolicyInfo';
    }
}
