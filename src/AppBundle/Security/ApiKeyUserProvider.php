<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

use AppBundle\Lib\ApiKey\ApiKeyManagerInterface;

class ApiKeyUserProvider implements UserProviderInterface
{
    protected $ApiKeyManager;

    public function __construct(ApiKeyManagerInterface $ApiKeyManager)
    {
        $this->ApiKeyManager = $ApiKeyManager;
    }

    public function getUserForApiKey($apiKey)
    {
        return $this->ApiKeyManager->getUserForApiKey($apiKey);
    }

    public function loadUserByUsername($username)
    {
        return null;
    }

    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass($class)
    {
        return 'Symfony\Component\Security\Core\User\AdvancedUserInterface' === $class;
    }
}
