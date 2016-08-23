<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyGetPolicyName extends XslPolicyBase
{
    public function getPolicyName($id)
    {
        $this->response = $this->mc->policyGetPolicyName($this->user->getId(), $id);
    }
}
