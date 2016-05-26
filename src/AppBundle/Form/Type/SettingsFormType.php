<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use AppBundle\Lib\Settings\SettingsManager;

class SettingsFormType extends AbstractType
{
    private $user;
    private $em;
    private $settings;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManager $em, SettingsManager $settings)
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
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('policy', 'choice', array(
                'choices' => array('Last used policy' => -2) + $this->em->getRepository('AppBundle:XslPolicyFile')->getUserAndSystemPoliciesChoices($this->user),
                'choices_as_values' => true,
                'placeholder' => 'No default policy',
                'required' => false,
                'label' => 'Default Policy',
                'data' => (-2 === $this->settings->getDefaultPolicy(false)) ? -2 : (($this->settings->getDefaultPolicy() instanceof \AppBundle\Entity\XslPolicyFile) ? $this->settings->getDefaultPolicy()->getId() : $this->settings->getDefaultPolicy()),
                'attr' => array('class' => 'policyList'))
                )
            ->add('display', 'choice', array(
                'choices' => array('Last used display' => -2) + $this->em->getRepository('AppBundle:DisplayFile')->getUserAndSystemDisplaysChoices($this->user),
                'choices_as_values' => true,
                'placeholder' => 'Default display (MediaConch Html)',
                'required' => false,
                'label' => 'Default Display',
                'data' => (-2 === $this->settings->getDefaultDisplay(false)) ? -2 : (($this->settings->getDefaultDisplay() instanceof \AppBundle\Entity\DisplayFile) ? $this->settings->getDefaultDisplay()->getId() : $this->settings->getDefaultPolicy()),
                'attr' => array('class' => 'displayList'))
                )
            ->add('verbosity', 'choice', array('choices' => array('Default verbosity level' => -1, 'Last used verbosity' => -2, '0 (least verbose)' => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, '5 (most verbose)' => 5),
                'choices_as_values' => true,
                'placeholder' => false,
                'required' => false,
                'label' => 'Default Verbosity',
                'data' => $this->settings->getDefaultVerbosity(false),
                'attr' => array('class' => 'verbosityList'))
                )
            ->add('save', 'submit', array('attr' => array('class' => 'btn-warning'), 'label' => 'Save settings'));
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
        return 'settings';
    }
}
