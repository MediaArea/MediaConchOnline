<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyEditType extends XslPolicyBase
{
    public function editType($policyId, $type)
    {
        $this->response = $this->mc->policyEditType($this->user->getId(), $policyId, $type);
    }
}
