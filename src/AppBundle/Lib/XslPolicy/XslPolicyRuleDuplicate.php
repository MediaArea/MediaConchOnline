<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyRuleDuplicate extends XslPolicyBase
{
    public function duplicate($id, $policyId)
    {
        $this->response = $this->mc->policyRuleDuplicate($this->user->getId(), $id, $policyId);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
