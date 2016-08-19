<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyDuplicate extends XslPolicyBase
{
    public function duplicate($policyId)
    {
        $this->response = $this->mc->policyDuplicate($this->user->getId(), $policyId);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
