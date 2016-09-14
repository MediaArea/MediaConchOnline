<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPoliciesNamesListResponse extends MediaConchServerAbstractResponse
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
            $this->status = true;
        }
        else if (isset($response->nok)) {
            throw new MediaConchServerException($response->nok->error);
        }
        else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
