<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyGetPoliciesNamesList extends XslPolicyBase
{
    public function getPoliciesNamesList()
    {
        $this->response = $this->mc->policyGetPoliciesNamesList($this->user->getId());
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
