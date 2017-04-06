<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPolicyResponse extends MediaConchServerAbstractResponse
{
    protected $policy;

    public function getPolicy()
    {
        return $this->policy;
    }

    protected function parse($response)
    {
        if (is_string($response)) {
            $response = json_decode($response);
            if (isset($response->policyTree)) {
                $this->policy = $response->policyTree;
                $this->status = true;
            } else {
                throw new MediaConchServerException('Unknown response');
            }
        } else {
            if (isset($response->policy)) {
                $this->policy = $response->policy;
                $this->status = true;
            } elseif (isset($response->nok)) {
                throw new MediaConchServerException($response->nok->error);
            } else {
                throw new MediaConchServerException('Unknown response');
            }
        }
    }
}
