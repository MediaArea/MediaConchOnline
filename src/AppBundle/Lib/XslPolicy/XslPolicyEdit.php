<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyEdit extends XslPolicyBase
{
    public function edit($policyId, $name, $description)
    {
        $this->response = $this->mc->policyEdit($this->user->getId(), $policyId, $name, $description);
    }
}
