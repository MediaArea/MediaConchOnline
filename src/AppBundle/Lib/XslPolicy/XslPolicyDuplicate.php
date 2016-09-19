<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyDuplicate extends XslPolicyBase
{
    public function duplicate($policyId, $dstPolicyId)
    {
        $this->response = $this->mc->policyDuplicate($this->user->getId(), $policyId, $dstPolicyId);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
