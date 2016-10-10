<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use FOS\UserBundle\Model\UserInterface;

use AppBundle\Controller\BaseController;

/**
 * @Route("/")
 */
class UserController extends BaseController
{

    /**
     * @Route("/settings")
     * @Template()
     */
    public function settingsAction(Request $request)
    {
        $form = $this->createForm('settings');

        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            $settings = $this->get('mco.settings');
            $settings->setDefaultPolicy($data['policy']);
            $settings->setDefaultDisplay($data['display']);
            $settings->setDefaultVerbosity($data['verbosity']);

            $this->addFlashBag('success', 'Settings successfully saved');

            return $this->redirect($this->generateUrl('app_user_settings'));
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/guest/register")
     * @Template()
     */
    public function guestRegisterAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->container->get('mco.guest.register.form');
        $formHandler = $this->container->get('mco.guest.register.form.handler');
        $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

        $process = $formHandler->process($user, $confirmationEnabled);
        if ($process) {
            $authUser = false;
            if ($confirmationEnabled) {
                $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());

                $route = 'fos_user_registration_check_email';
            } else {
                $authUser = true;
                $route = 'fos_user_registration_confirmed';
            }

            $this->addFlashBag('success', 'The user has been registered successfully');
            $url = $this->container->get('router')->generate($route);
            $response = new RedirectResponse($url);

            if ($authUser) {
                $this->authenticateUser($user, $response);

                // Switch role to basic
                $user->removeRole('ROLE_GUEST');
                $user->addRole('ROLE_BASIC');
                $this->container->get('fos_user.user_manager')->updateUser($user);

                // Set quotas for new user with mail activation disabled
                $this->container->get('mediaconch_user.quotas')->resetQuotas();
            }

            return $response;
        }

        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:Registration:guest_register.html.'.$this->container->getParameter('fos_user.template.engine'),
            array('form' => $form->createView())
        );
    }

    /**
     * Authenticate a user with Symfony Security
     *
     * @param \FOS\UserBundle\Model\UserInterface        $user
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    protected function authenticateUser(UserInterface $user, Response $response)
    {
        try {
            $this->container->get('fos_user.security.login_manager')->loginUser(
                $this->container->getParameter('fos_user.firewall_name'),
                $user,
                $response);
        } catch (AccountStatusException $ex) {
            // We simply do not authenticate users which do not pass the user
            // checker (not enabled, expired, etc.).
        }
    }
}
