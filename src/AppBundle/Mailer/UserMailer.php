<?php

namespace AppBundle\Mailer;

use FOS\UserBundle\Mailer\Mailer as FosMailer;
use FOS\UserBundle\Model\UserInterface;

class UserMailer extends FosMailer
{
    public function sendConfirmationEmailMessage(UserInterface $user)
    {
        // Store context and update it with config options
        $context = $this->router->getContext();
        $tmp = array('host' => $context->getHost(),
            'scheme' => $context->getScheme(),
            'baseUrl' => $context->getBaseUrl(),
            );

        if (null !== $this->parameters['absolute_url_for_mail']['host']) {
            $context->setHost($this->parameters['absolute_url_for_mail']['host']);
        }
        if (null !== $this->parameters['absolute_url_for_mail']['scheme']) {
            $context->setScheme($this->parameters['absolute_url_for_mail']['scheme']);
        }
        if (null !== $this->parameters['absolute_url_for_mail']['baseUrl']) {
            $context->setBaseUrl($this->parameters['absolute_url_for_mail']['baseUrl']);
        }

        parent::sendConfirmationEmailMessage($user);

        // Restore context
        $context->setHost($tmp['host']);
        $context->setScheme($tmp['scheme']);
        $context->setBaseUrl($tmp['baseUrl']);
    }

    public function sendResettingEmailMessage(UserInterface $user)
    {
        // Store context and update it with config options
        $context = $this->router->getContext();
        $tmp = array('host' => $context->getHost(),
            'scheme' => $context->getScheme(),
            'baseUrl' => $context->getBaseUrl(),
            );

        if (null !== $this->parameters['absolute_url_for_mail']['host']) {
            $context->setHost($this->parameters['absolute_url_for_mail']['host']);
        }
        if (null !== $this->parameters['absolute_url_for_mail']['scheme']) {
            $context->setScheme($this->parameters['absolute_url_for_mail']['scheme']);
        }
        if (null !== $this->parameters['absolute_url_for_mail']['baseUrl']) {
            $context->setBaseUrl($this->parameters['absolute_url_for_mail']['baseUrl']);
        }

        parent::sendResettingEmailMessage($user);

        // Restore context
        $context->setHost($tmp['host']);
        $context->setScheme($tmp['scheme']);
        $context->setBaseUrl($tmp['baseUrl']);
    }
}
