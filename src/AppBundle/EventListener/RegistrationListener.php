<?php

namespace AppBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegistrationListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_COMPLETED => 'onRegistrationCompleted',
            FOSUserEvents::REGISTRATION_CONFIRMED => 'onRegistrationConfirmed',
        );
    }

    public function onRegistrationCompleted(FilterUserResponseEvent $event)
    {
        $user = $event->getUser();
        // Add basic role
        $user->addRole('ROLE_BASIC');
    }

    public function onRegistrationConfirmed(FilterUserResponseEvent $event)
    {
        $user = $event->getUser();
        // Remove guest role
        if ($user->hasRole('ROLE_GUEST')) {
            $user->removeRole('ROLE_GUEST');
        }
        // Add basic role
        if (!$user->hasRole('ROLE_BASIC')) {
            $user->addRole('ROLE_BASIC');
        }
    }
}
