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

        if ($this->response->getStatus()) {
            foreach ($this->response->getPolicies() as $policy) {
                // Handle policy without name
                if ('' == $name = $policy->name) {
                    $name = 'Untitled policy';
                }

                // Handle policy with duplicate name
                if (isset($policyList[$name])) {
                    $policyName = $this->incrementPolicyName($name, $policyList);
                    $policyList[$policyName] = $policy->id;
                }
                else {
                    $policyList[$name] = $policy->id;
                }
            }
        }

        return $policyList;
    }

    protected function incrementPolicyName($name, $list, $increment = 1)
    {
        $incrementName = $name . ' - ' . $increment;
        if (isset($list[$incrementName])) {
            return $this->incrementPolicyName($name, $list, ++$increment);
        }
        else {
            return $incrementName;
        }
    }
}
