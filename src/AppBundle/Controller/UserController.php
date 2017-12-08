<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use AppBundle\Form\Type\SettingsFormType;
use AppBundle\Lib\Quotas\Quotas;
use AppBundle\Form\Type\GuestRegisterFormType;

/**
 * @Route("/")
 */
class UserController extends BaseController
{
    /**
     * Show the user.
     *
     * @Route("/profile/")
     */
    public function showProfileAction(Quotas $quotas)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('@FOSUser/Profile/show.html.twig', array(
            'user' => $user, 'quotas' => $quotas->getQuotasForProfile(),
        ));
    }

    /**
     * @Route("/settings")
     * @Template()
     */
    public function settingsAction(Request $request)
    {
        $form = $this->createForm(SettingsFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $settings = $this->get('mco.settings');
            $settings->setDefaultPolicy($data['policy']);
            $settings->setDefaultDisplay($data['display']);
            $settings->setDefaultVerbosity($data['verbosity']);

            $this->addFlashBag('success', 'Settings successfully saved');

            return $this->redirectToRoute('app_user_settings');
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/guest/register")
     */
    public function guestRegisterAction(Request $request, Quotas $quotas)
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface || !$user->hasRole('ROLE_GUEST')) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->createForm(GuestRegisterFormType::class, $user);
        $confirmation = $this->getParameter('fos_user.registration.confirmation.enabled');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEnabled(true);

            $this->addFlashBag('success', 'You have been registered successfully');

            if ($confirmation) {
                $this->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
                $route = 'fos_user_registration_check_email';

                // Keep guest user enable to avoid redirect to login page
                if (null === $user->getConfirmationToken()) {
                    $user->setConfirmationToken($this->get('fos_user.util.token_generator')->generateToken());
                }

                $this->get('fos_user.mailer')->sendConfirmationEmailMessage($user);
            } else {
                $route = 'fos_user_registration_confirmed';

                // Remove guest token cookie
                $request->attributes->set('remove_guest_cookie', true);

                // Switch role to basic
                $user->removeRole('ROLE_GUEST');
                $user->addRole('ROLE_BASIC');

                // Remove guest token in DB
                $token = $user->getGuestToken();
                $user->setGuestToken(null);
                $em = $this->getDoctrine()->getManager();
                $em->remove($token);

                // Set quotas for new user with mail activation disabled
                $quotas->resetQuotas();
            }

            // Update user
            $this->get('fos_user.user_manager')->updateUser($user);

            return $this->redirectToRoute($route);
        }

        return $this->render('@FOSUser/Registration/guest_register.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
