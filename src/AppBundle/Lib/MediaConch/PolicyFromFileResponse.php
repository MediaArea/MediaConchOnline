<?php

namespace AppBundle\Lib\MediaConch;

class PolicyFromFileResponse extends MediaConchServerAbstractResponse
{
    protected $policyId;

    public function getPolicyId()
    {
        return $this->policyId;
    }

    protected function parse($response)
    {
        if (isset($response->policy_id)) {
            $this->policyId = $response->policy_id;
            $this->status = true;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
