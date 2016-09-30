<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use AppBundle\Controller\MCOControllerInterface;

class GuestListener
{
    protected $firewallName;
    protected $authorizationChecker;

    public function __construct($firewallName, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->firewallName = $firewallName;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function guestAuthentication(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        if (!$controller[0] instanceof MCOControllerInterface) {
            return;
        }

        $ctrl = $controller[0];
        // Create anonymous user
        if (null == $ctrl->getUser()) {
            $userManager = $ctrl->get('fos_user.user_manager');
            // Generate unique username
            do {
                $username = $this->generateRandomGuestUsername();
            }
            while (null !== $userManager->findUserByUsername($username));

            // Create user
            $user = $ctrl->get('fos_user.util.user_manipulator')->create(
                $username,
                $ctrl->get('fos_user.util.token_generator')->generateToken(),
                $username . '@mco',
                1,
                0);

            // Add guest role
            $user->addRole('ROLE_GUEST');
            $userManager->updateUser($user);

            // Log in user
            $ctrl->get('fos_user.security.login_manager')->loginUser(
                $this->firewallName,
                $user);

            // Force _remember_me value to yes
            $event->getRequest()->query->set('_remember_me', 'yes');

            // Set rememberme cookie in fake response
            $response = new Response();
            $rememberMeServices = $ctrl->get('mco.security.authentication.rememberme.services.simplehash');
            $rememberMeServices->loginSuccess($event->getRequest(), $response, $ctrl->get('security.token_storage')->getToken());

            // Save cookie in attributes if exists
            $cookies = $response->headers->getCookies();
            if (isset($cookies[0])) {
                $event->getRequest()->attributes->set('remember_me_guest_cookie', $cookies[0]);
            }
        }

        // Check if user have at least ROLE_GUEST
        if (false === $this->authorizationChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }
    }

    public function rememberMeGuestCookie(FilterResponseEvent $event)
    {
        // Check if cookie is present
        if (!$cookie = $event->getRequest()->attributes->get('remember_me_guest_cookie')) {
            return;
        }

        $response = $event->getResponse();

        // Add cookie in response
        $response->headers->setCookie($cookie);
    }

    protected function generateRandomGuestUsername()
    {
        if (false == preg_match('/([0-9]{6})/', uniqid(mt_rand(), true), $matches)) {
            return $this->generateRandomGuestUsername();
        }

        return 'guest' . $matches[1];
    }
}
