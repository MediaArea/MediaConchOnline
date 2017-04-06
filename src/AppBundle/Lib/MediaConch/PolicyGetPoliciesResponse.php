<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPoliciesResponse extends MediaConchServerAbstractResponse
{
    protected $policies;

    public function getPolicies()
    {
        return $this->policies;
    }

    protected function parse($response)
    {
        if (is_string($response)) {
            $response = json_decode($response);
            if (isset($response->policiesTree)) {
                $this->policies = $response->policiesTree;
                $this->status = true;
            } else {
                throw new MediaConchServerException('Unknown response');
            }
        } else {
            if (isset($response->policies)) {
                $this->policies = $response->policies;
                $this->status = true;
            } elseif (isset($response->nok)) {
                $this->error = $response->nok->error;
            } else {
                throw new MediaConchServerException('Unknown response');
            }
        }
    }
}
