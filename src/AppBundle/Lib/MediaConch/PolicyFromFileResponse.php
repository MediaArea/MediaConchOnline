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
        } elseif (isset($response->nok)) {
            throw new MediaConchServerException($response->nok->error);
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
