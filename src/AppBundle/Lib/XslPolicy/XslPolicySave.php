<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicySave extends XslPolicyBase
{
    public function save($policyId)
    {
        $this->response = $this->mc->policySave($this->user->getId(), $policyId);
    }
}
