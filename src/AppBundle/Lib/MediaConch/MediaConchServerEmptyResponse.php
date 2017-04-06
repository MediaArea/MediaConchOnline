<?php

namespace AppBundle\Lib\MediaConch;

class MediaConchServerEmptyResponse extends MediaConchServerAbstractResponse
{
    protected function parse($response)
    {
        if (isset($response->nok)) {
            throw new MediaConchServerException($response->nok->error);
        } elseif (isset($response) && is_object($response)) {
            $this->status = true;
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
