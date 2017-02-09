<?php

namespace AppBundle\Lib\MediaConch;

class ValidateResponse extends MediaConchServerAbstractResponse
{
    protected $valid;

    public function getValid()
    {
        return $this->valid;
    }

    protected function parse($response)
    {
        if (isset($response->ok) && is_array($response->ok) && isset($response->ok[0])) {
            $this->valid = $response->ok[0]->valid;
            $this->status = true;
        }
        else if (isset($response->nok) && is_array($response->nok) && isset($response->nok[0])) {
            throw new MediaConchServerException($response->nok[0]->error);
        }
        else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
