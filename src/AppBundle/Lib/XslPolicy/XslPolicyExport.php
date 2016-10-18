<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyExport extends XslPolicyBase
{
    public function export($id)
    {
        $this->response = $this->mc->policyExport($this->user->getId(), $id);
    }

    public function publicExport($id, $userId)
    {
        $this->response = $this->mc->policyExport($userId, $id, true);
    }

    public function getPolicyXml()
    {
        return $this->response->getXml();
    }
}
