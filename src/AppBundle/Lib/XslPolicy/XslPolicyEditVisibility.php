<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyEditVisibility extends XslPolicyBase
{
    public function editVisibility($policyId, $visibility)
    {
        $this->response = $this->mc->policyEditVisibility($this->user->getId(), $policyId, $visibility);
    }
}
