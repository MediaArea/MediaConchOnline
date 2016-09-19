<?php

namespace AppBundle\Lib\MediaConch;

use AppBundle\Lib\MediaConch\MediaConchServerException;

abstract class MediaConchServerAbstractResponse
{
    protected $error;
    protected $status = false;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getError()
    {
        return $this->error;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
