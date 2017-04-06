<?php

namespace AppBundle\Lib\MediaConch;

class ValuesFromTypeResponse extends MediaConchServerAbstractResponse
{
    protected $values;

    public function getValues()
    {
        return $this->values;
    }

    protected function parse($response)
    {
        if (isset($response->values)) {
            $this->values = $response->values;
            $this->status = true;
        } elseif (isset($response->nok)) {
            throw new MediaConchServerException($response->nok);
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
