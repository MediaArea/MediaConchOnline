<?php

namespace AppBundle\Lib\Settings;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use AppBundle\Entity\Settings;

class SettingsManager
{
    protected $user;
    protected $em;
    protected $repository;
    protected $userSettings;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManager $em)
    {
        $token = $tokenStorage->getToken();
        if ($token !== null && $token->getUser() instanceof \AppBundle\Entity\User) {
            $this->user = $token->getUser();
        }
        else {
            throw new \Exception('Invalid User');
        }

        $this->em = $em;
        $this->repository = $this->em->getRepository('AppBundle:Settings');
    }

    public function setDefaultPolicy($policy)
    {
        if ($policy instanceof \AppBundle\Entity\XslPolicyFile) {
            $this->setSetting('defaultPolicy', $policy->getId());
        }
        // If last used policy
        else if (is_int($policy)) {
            $this->setSetting('defaultPolicy', $policy);
        }
        else {
            $this->setSetting('defaultPolicy', null);
        }


        return $this;
    }

    public function getDefaultPolicy($lastUsed = true, $default = null)
    {
        $defaultPolicy = $this->getSetting('defaultPolicy', $default);
        if (-2 === $defaultPolicy) {
            if ($lastUsed) {
                return $this->getLastUsedPolicy();
            }
            else {
                return -2;
            }
        }
        elseif (null !== $defaultPolicy) {
            return $this->em->getRepository('AppBundle:XslPolicyFile')->findOneByUserOrSystem($defaultPolicy, $this->user);
        }
    }

    public function setDefaultDisplay($display)
    {
        if ($display instanceof \AppBundle\Entity\DisplayFile) {
            $this->setSetting('defaultDisplay', $display->getId());
        }
        // If last used display
        else if (is_int($display)) {
            $this->setSetting('defaultDisplay', $display);
        }
        else {
            $this->setSetting('defaultDisplay', null);
        }

        return $this;
    }

    public function getDefaultDisplay($lastUsed = true, $default = null)
    {
        $defaultDisplay = $this->getSetting('defaultDisplay', $default);
        if (-2 === $defaultDisplay) {
            if ($lastUsed) {
                return $this->getLastUsedDisplay();
            }
            else {
                return -2;
            }
        }
        elseif (null !== $defaultDisplay) {
            return $this->em->getRepository('AppBundle:DisplayFile')->findOneByUserOrSystem($defaultDisplay, $this->user);
        }
    }

    public function setDefaultVerbosity($verbosity)
    {
        $this->setSetting('defaultVerbosity', $verbosity);

        return $this;
    }

    public function getDefaultVerbosity($lastUsed = true, $default = -1)
    {
        $defaultVerbosity = $this->getSetting('defaultVerbosity', $default);
        if (-2 === $defaultVerbosity) {
            if ($lastUsed) {
                return $this->getLastUsedVerbosity();
            }
            else {
                return -2;
            }
        }
        else {
            return $defaultVerbosity;
        }
    }

    public function setLastUsedPolicy($policy) {
        if ($policy instanceof \AppBundle\Entity\XslPolicyFile && -2 === $this->getDefaultPolicy(false)) {
            $this->setSetting('lastUsedPolicy', $policy->getId());
        }
        else {
            $this->removeSetting('lastUsedPolicy');
        }

        return $this;
    }

    public function getLastUsedPolicy() {
        $lastUsedPolicy = $this->getSetting('lastUsedPolicy');
        if (null !== $lastUsedPolicy) {
            return $this->em->getRepository('AppBundle:XslPolicyFile')->findOneByUserOrSystem($lastUsedPolicy, $this->user);
        }
    }

    public function setLastUsedDisplay($display) {
        if ($display instanceof \AppBundle\Entity\DisplayFile && -2 === $this->getDefaultDisplay(false)) {
            $this->setSetting('lastUsedDisplay', $display->getId());
        }
        else {
            $this->removeSetting('lastUsedDisplay');
        }

        return $this;
    }

    public function getLastUsedDisplay() {
        $lastUsedDisplay = $this->getSetting('lastUsedDisplay');
        if (null !== $lastUsedDisplay) {
            return $this->em->getRepository('AppBundle:DisplayFile')->findOneByUserOrSystem($lastUsedDisplay, $this->user);
        }
    }

    public function setLastUsedVerbosity($verbosity) {
        if (-2 === $this->getDefaultVerbosity(false)) {
            $this->setSetting('lastUsedVerbosity', $verbosity);
        }
        else {
            $this->removeSetting('lastUsedVerbosity');
        }

        return $this;
    }

    public function getLastUsedVerbosity() {
        return $this->getSetting('lastUsedVerbosity');
    }

    protected function setSetting($name, $value)
    {
        $this->loadSettings();

        if (!array_key_exists($name, $this->userSettings)) {
            $setting = new Settings();
            $setting->setUser($this->user);
            $setting->setName($name);

            $this->em->persist($setting);
        }
        else {
            $setting = $this->repository->findOneById($this->userSettings[$name]['id']);
        }

        $setting->setValue(serialize($value));
        $this->em->flush();

        return $this;
    }

    protected function getSetting($name, $default = null)
    {
        $this->loadSettings();

        if (array_key_exists($name, $this->userSettings)) {
            return $this->userSettings[$name]['value'];
        }
        else {
            return $default;
        }
    }

    protected function removeSetting($name) {
        $this->loadSettings();

        if (array_key_exists($name, $this->userSettings)) {
            $setting = $this->repository->findOneById($this->userSettings[$name]['id']);
            $this->em->remove($setting);
            $this->em->flush();

            unset($this->userSettings[$name]);
        }
    }

    protected function loadSettings() {
        if (null === $this->userSettings) {
            $this->userSettings = array();

            // Get settings from DB
            foreach ($this->repository->findByUser($this->user) as $setting) {
                $this->userSettings[$setting->getName()] = array('id' => $setting->getId(),
                    'value' => unserialize($setting->getValue())
                    );
            }
        }

        return $this;
    }
}
