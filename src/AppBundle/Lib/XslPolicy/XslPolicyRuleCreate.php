<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyRuleCreate extends XslPolicyBase
{
    public function create($policyId)
    {
        $this->response = $this->mc->policyRuleCreate($this->user->getId(), $policyId);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
