<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyImport extends XslPolicyBase
{
    public function import($xml)
    {
        $this->response = $this->mc->policyImport($this->user->getId(), $xml);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
