<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

use AppBundle\Controller\MCOControllerInterface;
use DeviceDetector\Parser\Bot AS BotParser;

class GuestListener
{
    protected $firewallName;
    protected $authorizationChecker;
    protected $tokenStorage;
    protected $rememberMeService;

    public function __construct($firewallName, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage, RememberMeServicesInterface $rememberMeServices)
    {
        $this->firewallName = $firewallName;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->rememberMeServices = $rememberMeServices;
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
        if (null == $ctrl->getUser()) {
            // Check bots
            $botParser = new BotParser();
            $botParser->discardDetails();
            $botParser->setUserAgent($event->getRequest()->headers->get('User-Agent'));

            $userManager = $ctrl->get('fos_user.user_manager');

            if (true === $botParser->parse()) {
                // Bot user
                $username = 'guest';

                // Create user if not exists
                if (null === $user = $userManager->findUserByUsername($username)) {
                    $user = $ctrl->get('fos_user.util.user_manipulator')->create(
                        $username,
                        $ctrl->get('fos_user.util.token_generator')->generateToken(),
                        $username . '@mco',
                        1,
                        0);

                    // Add guest role
                    $user->addRole('ROLE_BOT');
                    $userManager->updateUser($user);
                }
            }
            else {
                // Guest user
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

                // Update last login date
                $user->setLastLogin(new \DateTime());

                // Add guest role
                $user->addRole('ROLE_GUEST');
                $userManager->updateUser($user);

                // Set remember_me attribute to add cookie in response
                $event->getRequest()->attributes->set('remember_me_guest_cookie', true);
            }

            // Log in user
            $ctrl->get('fos_user.security.login_manager')->loginUser(
                $this->firewallName,
                $user);
        }

        // Check if user have at least ROLE_GUEST
        if (false === $this->authorizationChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }
    }

    public function rememberMeGuestCookie(FilterResponseEvent $event)
    {
        // Check if remember_me attribute has been set and add cookie to response
        if (true == $event->getRequest()->attributes->get('remember_me_guest_cookie')) {
            // Force _remember_me value to yes
            $event->getRequest()->query->set('_remember_me', 'yes');

            // Set rememberme cookie by calling loginSuccess
            $this->rememberMeServices->loginSuccess($event->getRequest(), $event->getResponse(), $this->tokenStorage->getToken());
        }
    }

    protected function generateRandomGuestUsername()
    {
        if (false == preg_match('/([0-9]{6})/', uniqid(mt_rand(), true), $matches)) {
            return $this->generateRandomGuestUsername();
        }

        return 'guest' . $matches[1];
    }
}
