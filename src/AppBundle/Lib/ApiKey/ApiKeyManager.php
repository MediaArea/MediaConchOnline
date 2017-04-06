<?php

namespace AppBundle\Lib\ApiKey;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use FOS\UserBundle\Util\TokenGeneratorInterface;

use AppBundle\Entity\ApiKey;

class ApiKeyManager implements ApiKeyManagerInterface
{
    protected $em;
    protected $requestStack;
    protected $tokenGenerator;
    protected $encoderFactory;

    public function __construct(EntityManager $em, RequestStack $requestStack, TokenGeneratorInterface $tokenGenerator, $encoderFactory)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->tokenGenerator = $tokenGenerator;
        $this->encoderFactory = $encoderFactory;
    }

    public function getUserForApiKey($apiKey)
    {
        $apiToken = $this->em->getRepository('AppBundle:ApiKey')->findOneByToken($apiKey);

        if (null === $apiToken) {
            return null;
        }

        $user = $apiToken->getUser();
        $user->addRole('ROLE_API');

        return $user;
    }


    /**
     * Create ApiKey for a user
     * @param \AppBundle\Entity\User $user
     * @param String $app
     * @param String $version
     *
     * @return ApiKey
     */
    public function getApiKeyForUser($username, $password, $app = null, $version = null)
    {
        $user = $this->em->getRepository('AppBundle:User')->findOneByUsername($username);

        if (!$user) {
            return null;
        }

        if (!$this->checkPassword($user, $password)) {
            return null;
        }

        $apiToken = $this->em->getRepository('AppBundle:ApiKey')->findOneBy(array('user' => $user, 'app' => $app));

        if (!$apiToken) {
            $apiToken = $this->createApiKey($user, $app, $version);
        }

        return $apiToken;
    }

    /**
     * Create ApiKey for a user
     * @param \AppBundle\Entity\User $user
     * @param String $app
     * @param String $version
     *
     * @return ApiKey
     */
    protected function createApiKey($user, $app = null, $version = null)
    {
        $apiKey = new ApiKey();
        $apiKey->setUser($user)
            ->setApp($app)
            ->setVersion($version)
            ->setIp($this->requestStack->getCurrentRequest()->getClientIp())
            ->setToken($this->tokenGenerator->generateToken());

        $this->em->persist($apiKey);
        $this->em->flush();

        return $apiKey;
    }

    /**
     * Check if a password is valid
     * @param \AppBundle\Entity\User $user
     * @param String $password
     *
     * @return bool
     */
    protected function checkPassword($user, $password)
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }
}
