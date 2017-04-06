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
        } elseif (isset($response->nok)) {
            throw new MediaConchServerException($response->nok->error);
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
