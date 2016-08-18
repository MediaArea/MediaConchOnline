<?php

namespace AppBundle\Lib\MediaConch;

class PolicyFromFileResponse
{
    private $policyId;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getPolicyId()
    {
        return $this->policyId;
    }

    public function getError()
    {
        return $this->error;
    }

    private function parse($response)
    {
        if (isset($response->policy_id)) {
            $this->policyId = $response->policy_id;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
