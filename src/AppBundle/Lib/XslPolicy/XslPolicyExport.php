<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyExport extends XslPolicyBase
{
    public function export($id)
    {
        $this->response = $this->mc->policyExport($this->user->getId(), $id);
    }

    public function getPolicyXml()
    {
        return $this->response->getXml();
    }
}
