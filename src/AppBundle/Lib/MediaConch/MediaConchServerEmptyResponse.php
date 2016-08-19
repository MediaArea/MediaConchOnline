<?php

namespace AppBundle\Lib\MediaConch;

class MediaConchServerEmptyResponse extends MediaConchServerAbstractResponse
{
    protected function parse($response)
    {
        if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else if (isset($response) && is_object($response)) {
            $this->status = true;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
