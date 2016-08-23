<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPolicyNameResponse extends MediaConchServerAbstractResponse
{
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    protected function parse($response)
    {
        if (isset($response->name)) {
            $this->name = $response->name;
            $this->status = true;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
