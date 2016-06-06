<?php

namespace AppBundle\Lib\MediaConch;

class PolicyFromFileResponse
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

    public function getError()
    {
        return $this->error;
    }

    private function parse($response)
    {
        if (isset($response->policy)) {
            $this->policy = $response->policy;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
