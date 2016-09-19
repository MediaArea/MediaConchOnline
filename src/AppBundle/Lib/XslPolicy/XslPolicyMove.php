<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyMove extends XslPolicyBase
{
    public function move($policyId, $dstPolicyId)
    {
        $this->response = $this->mc->policyMove($this->user->getId(), $policyId, $dstPolicyId);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
