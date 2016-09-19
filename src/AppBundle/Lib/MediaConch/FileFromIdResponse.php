<?php

namespace AppBundle\Lib\MediaConch;

class FileFromIdResponse extends MediaConchServerAbstractResponse
{
    protected $file;

    public function getFile()
    {
        return $this->file;
    }

    protected function parse($response)
    {
        if (isset($response->file)) {
            $this->file = $response->file;
            $this->status = true;
        }
        else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
