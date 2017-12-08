<?php

namespace AppBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManager;
use AppBundle\Lib\Settings\SettingsManager;
use AppBundle\Lib\XslPolicy\XslPolicyGetPoliciesNamesList;
use AppBundle\Lib\MediaConch\MediaConchServerException;

class CheckerBaseFormType extends AbstractType
{
    protected $user;
    protected $em;
    protected $settings;
    protected $policyList;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManager $em, SettingsManager $settings, XslPolicyGetPoliciesNamesList $policyList)
    {
        $token = $tokenStorage->getToken();
        if (null !== $token && $token->getUser() instanceof \AppBundle\Entity\User) {
            $this->user = $token->getUser();
        } else {
            throw new \Exception('Invalid User');
        }

        $this->em = $em;
        $this->settings = $settings;

        try {
            $this->policyList = $policyList;
            $this->policyList->getPoliciesNamesList();
            $this->policyList = $this->policyList->getListForChoiceForm();
        } catch (MediaConchServerException $e) {
            $this->policyList = array();
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('policy', ChoiceType::class, array(
                'choices' => $this->policyList,
                'placeholder' => 'Choose a policy',
                'required' => false,
                'label' => 'Policy',
                'data' => $this->settings->getDefaultPolicy(),
                'attr' => array('class' => 'policyList'),
            ))
            ->add('display', EntityType::class, array(
                'class' => 'AppBundle:DisplayFile',
                'choices' => $this->em->getRepository('AppBundle:DisplayFile')->getUserAndSystemDisplays($this->user),
                'placeholder' => 'Choose a display',
                'required' => false,
                'label' => 'Display',
                'data' => $this->settings->getDefaultDisplay(),
                'attr' => array('class' => 'displayList'),
            ))
            ->add('verbosity', ChoiceType::class, array(
                'choices' => array(
                    'Default level' => -1,
                    '0 (least verbose)' => 0,
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4, '5 (most verbose)' => 5,
                    ),
                'placeholder' => false,
                'required' => false,
                'label' => 'Verbosity',
                'data' => $this->settings->getDefaultVerbosity(),
                'attr' => array('class' => 'verbosityList'),
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public function getBlockPrefix()
    {
        return 'form';
    }
}
