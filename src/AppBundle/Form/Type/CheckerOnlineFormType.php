<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use AppBundle\Lib\Settings\SettingsManager;
use AppBundle\Lib\XslPolicy\XslPolicyGetPoliciesNamesList;

class CheckerOnlineFormType extends AbstractType
{
    private $user;
    private $em;
    private $settings;
    private $policyList;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManager $em, SettingsManager $settings, XslPolicyGetPoliciesNamesList $policyList)
    {
        $token = $tokenStorage->getToken();
        if ($token !== null && $token->getUser() instanceof \AppBundle\Entity\User) {
            $this->user = $token->getUser();
        }
        else {
            throw new \Exception('Invalid User');
        }

        $this->em = $em;
        $this->settings = $settings;

        $this->policyList = $policyList;
        $this->policyList->getPoliciesNamesList();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('policy', 'choice', array('choices' => $this->policyList->getListForChoiceForm(),
                'choices_as_values' => true,
                'placeholder' => 'Choose a policy',
                'required' => false,
                'label' => 'Policy',
                'data' => $this->settings->getDefaultPolicy(),
                'attr' => array('class' => 'policyList'))
                )
            ->add('display', 'entity', array('class' => 'AppBundle:DisplayFile',
                'choices' => $this->em->getRepository('AppBundle:DisplayFile')->getUserAndSystemDisplays($this->user),
                'placeholder' => 'Choose a display',
                'required' => false,
                'label' => 'Display',
                'data' => $this->settings->getDefaultDisplay(),
                'attr' => array('class' => 'displayList'))
                )
            ->add('verbosity', 'choice', array('choices' => array('Default level' => -1, '0 (least verbose)' => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, '5 (most verbose)' => 5),
                'choices_as_values' => true,
                'placeholder' => false,
                'required' => false,
                'label' => 'Verbosity',
                'data' => $this->settings->getDefaultVerbosity(),
                'attr' => array('class' => 'verbosityList'))
                )
            ->add('file', 'url', array('attr' => array('pattern' => '.{10,512}'), 'label' => 'URL of file'))
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
        return 'checkerOnline';
    }
}
