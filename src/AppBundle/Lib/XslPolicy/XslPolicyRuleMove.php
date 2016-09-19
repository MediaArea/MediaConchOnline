<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyRuleMove extends XslPolicyBase
{
    public function move($id, $policyId, $dstPolicyId)
    {
        $this->response = $this->mc->policyRuleMove($this->user->getId(), $id, $policyId, $dstPolicyId);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
