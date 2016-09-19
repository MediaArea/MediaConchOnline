<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyGetPolicy extends XslPolicyBase
{
    public function getPolicy($id, $format = 'JSON')
    {
        $this->response = $this->mc->policyGetPolicy($this->user->getId(), $id, $format);
    }
}
