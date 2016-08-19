<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyDelete extends XslPolicyBase
{
    public function delete($policyId)
    {
        $this->response = $this->mc->policyDelete($this->user->getId(), $policyId);
    }
}
