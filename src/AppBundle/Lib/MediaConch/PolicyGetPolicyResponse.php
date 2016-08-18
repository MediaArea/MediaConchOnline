<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPolicyResponse
{
    private $policy;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

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
            }
            else {
                throw new \Exception('Unknown response');
            }
        }
        else {
            if (isset($response->policy)) {
                $this->policy = $response->policy;
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
