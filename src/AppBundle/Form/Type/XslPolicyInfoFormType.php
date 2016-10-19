<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
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
            ->add('policyDescription', 'textarea', array('required' => false))
            ->add('policyType', 'choice', array('choices' => array('AND' => 'and', 'OR' => 'or'),
                'choices_as_values' => true,
                'placeholder' => false)
                )
            ->add('policyLicense', 'choice', array('choices' => array('MIT License' => 'MIT', 'Apache license version 2' => 'Apache-2.0', 'GNU GPL version 3 or later' => 'GPL-3.0+', 'Other' => 'Other'),
                'choices_as_values' => true,
                'placeholder' => false)
                )
            ->add('policyTopLevel', 'hidden');

        if ($this->authChecker->isGranted('ROLE_BASIC')) {
            $builder->add('policyVisibility', 'choice', array('choices' => array('Private' => false, 'Public' => true),
                'choices_as_values' => true,
                'placeholder' => false)
                );
        }

        $builder->add('SavePolicyInfo', 'submit', array('attr' => array('class' => 'btn-warning')));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getName()
    {
        return 'xslPolicyInfo';
    }
}
