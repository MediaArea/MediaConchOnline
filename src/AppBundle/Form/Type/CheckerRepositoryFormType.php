<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

class CheckerRepositoryFormType extends AbstractType
{
    private $user;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManager $em)
    {
        $token = $tokenStorage->getToken();
        if ($token !== null && $token->getUser() instanceof \AppBundle\Entity\User) {
            $this->user = $token->getUser();
        }
        else {
            throw new \Exception('Invalid User');
        }

        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('policy', 'entity', array('class' => 'AppBundle:XslPolicyFile',
                'choices' => $this->em->getRepository('AppBundle:XslPolicyFile')->getUserAndSystemPolicies($this->user),
                'placeholder' => 'Choose a policy',
                'required' => false,
                'label' => 'Policy')
                )
            ->add('display', 'entity', array('class' => 'AppBundle:DisplayFile',
                'choices' => $this->em->getRepository('AppBundle:DisplayFile')->getUserAndSystemDisplays($this->user),
                'placeholder' => 'Choose a display',
                'required' => false,
                'label' => 'Display')
                )
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
