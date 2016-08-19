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
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
