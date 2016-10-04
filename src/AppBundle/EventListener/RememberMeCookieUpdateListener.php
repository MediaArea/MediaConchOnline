<?php

namespace AppBundle\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class RememberMeCookieUpdateListener
{
    protected $tokenStorage;
    protected $rememberMeService;

    public function __construct(TokenStorageInterface $tokenStorage, RememberMeServicesInterface $rememberMeServices)
    {
        $this->tokenStorage = $tokenStorage;
        $this->rememberMeServices = $rememberMeServices;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        // Set remember_me attribute to add cookie in response
        $event->getRequest()->attributes->set('remember_me_update_cookie', true);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function rememberMeUpdateCookie(FilterResponseEvent $event)
    {
        // Check if remember_me update attribute has been set and add cookie to response
        if (true === $event->getRequest()->attributes->get('remember_me_update_cookie')) {
            // Force _remember_me value to yes
            $event->getRequest()->query->set('_remember_me', 'yes');

            // Set rememberme cookie by calling loginSuccess
            $this->rememberMeServices->loginSuccess($event->getRequest(), $event->getResponse(), $this->tokenStorage->getToken());
        }
    }
}
