<?php

namespace AppBundle\Lib\MediaConch;

class MediaConchServerErrorResponse extends MediaConchServerAbstractResponse
{
    public function __construct($response)
    {
        $this->error = $response;
    }
}
