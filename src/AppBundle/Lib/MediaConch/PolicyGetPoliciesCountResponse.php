<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetPoliciesCountResponse extends MediaConchServerAbstractResponse
{
    protected $count;

    public function getCount()
    {
        return $this->count;
    }

    protected function parse($response)
    {
        if (isset($response->size)) {
            $this->count = $response->size;
            $this->status = true;
        } elseif (isset($response->nok)) {
            throw new MediaConchServerException($response->nok->error);
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
