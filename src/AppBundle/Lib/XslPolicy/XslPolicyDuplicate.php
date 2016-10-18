<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyDuplicate extends XslPolicyBase
{
    public function duplicate($policyId, $dstPolicyId)
    {
        $this->response = $this->mc->policyDuplicate($this->user->getId(), $policyId, $dstPolicyId);
    }

    public function publicDuplicate($policyId, $userId)
    {
        $this->response = $this->mc->policyDuplicate($userId, $policyId, -1, $this->user->getId(), true);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
