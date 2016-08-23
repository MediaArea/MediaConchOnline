<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyRuleDuplicate extends XslPolicyBase
{
    public function duplicate($id, $policyId, $dstPolicyId)
    {
        $this->response = $this->mc->policyRuleDuplicate($this->user->getId(), $id, $policyId, $dstPolicyId);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
