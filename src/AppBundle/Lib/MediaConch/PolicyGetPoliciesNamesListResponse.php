<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPoliciesNamesListResponse
{
    private $policies;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getPolicies()
    {
        return $this->policies;
    }

    protected function parse($response)
    {
        if (isset($response->policies)) {
            $this->policies = $response->policies;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
