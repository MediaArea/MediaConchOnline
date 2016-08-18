<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPoliciesResponse
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
        if (is_string($response)) {
            $response = json_decode($response);
            if (isset($response->policiesTree)) {
                $this->policies = $response->policiesTree;
            }
            else {
                throw new \Exception('Unknown response');
            }
        }
        else {
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
}
