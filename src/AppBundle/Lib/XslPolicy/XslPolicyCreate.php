<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyCreate extends XslPolicyBase
{
    public function create($type = null, $parentId = null)
    {
        $this->response = $this->mc->policyCreate($this->user->getId(), $type, $parentId);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
