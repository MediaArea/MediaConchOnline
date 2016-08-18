<?php

namespace AppBundle\Lib\XslPolicy;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use AppBundle\Lib\MediaConch\MediaConchServer;

class XslPolicyGetPoliciesNamesList
{
    protected $response;
    protected $user;

    public function __construct(MediaConchServer $mc, TokenStorageInterface $tokenStorage)
    {
        $this->mc = $mc;

        $token = $tokenStorage->getToken();
        if ($token !== null && $token->getUser() instanceof \AppBundle\Entity\User) {
            $this->user = $token->getUser();
        }
        else {
            throw new \Exception('Invalid User');
        }
    }

    public function getPoliciesNamesList()
    {
        $this->response = $this->mc->policyGetPoliciesNamesList($this->user->getId());
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getListForChoiceForm()
    {
        $policyList = array();
        foreach ($this->response->getPolicies() as $policy) {
            $policyList[$policy->name] = $policy->id;
        }

        return $policyList;
    }
}
