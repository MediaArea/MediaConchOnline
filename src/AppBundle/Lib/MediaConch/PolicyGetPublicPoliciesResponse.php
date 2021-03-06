<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPublicPoliciesResponse extends MediaConchServerAbstractResponse
{
    protected $policies;

    public function getPolicies()
    {
        return $this->policies;
    }

    protected function parse($response)
    {
        if (isset($response->policies)) {
            $this->policies = $response->policies;
        } elseif (isset($response->nok)) {
            $this->error = $response->nok->error;
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
