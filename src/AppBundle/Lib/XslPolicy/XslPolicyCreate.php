<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyCreate extends XslPolicyBase
{
    public function create($parentId, $type = null)
    {
        $this->response = $this->mc->policyCreate($this->user->getId(), $parentId, $type);
    }

    public function getCreatedId()
    {
        return $this->response->getId();
    }
}
