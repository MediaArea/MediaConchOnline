<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyRuleDelete extends XslPolicyBase
{
    public function delete($id, $policyId)
    {
        $this->response = $this->mc->policyRuleDelete($this->user->getId(), $id, $policyId);
    }
}
