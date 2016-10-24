<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyEdit extends XslPolicyBase
{
    public function edit($policyId, $name, $description, $license)
    {
        $this->response = $this->mc->policyEdit($this->user->getId(), $policyId, $name, $description, $license);
    }
}
