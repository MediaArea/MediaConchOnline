<?php

namespace AppBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResettingListener implements EventSubscriberInterface
{
    protected $session;
    protected $router;

    public function __construct(SessionInterface $session, UrlGeneratorInterface $router)
    {
        $this->session = $session;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE => 'onResettingSendEmailInitialize',
        );
    }

    public function onResettingSendEmailInitialize(GetResponseNullableUserEvent $event)
    {
        $user = $event->getUser();
        if (is_object($user) && $user instanceof UserInterface && $user->hasRole('ROLE_GUEST')) {
            // Add flash
            $this->session->getFlashBag()->add('info', 'You cannot reset password for a guest user');

            // Set response
            $event->setResponse(new RedirectResponse($this->router->generate('fos_user_resetting_request')));
        }
    }
}
